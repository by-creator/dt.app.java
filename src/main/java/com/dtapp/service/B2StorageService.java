package com.dtapp.service;

import org.springframework.beans.factory.ObjectProvider;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.stereotype.Service;
import org.springframework.web.multipart.MultipartFile;
import software.amazon.awssdk.core.sync.RequestBody;
import software.amazon.awssdk.services.s3.S3Client;
import software.amazon.awssdk.services.s3.model.CommonPrefix;
import software.amazon.awssdk.services.s3.model.Delete;
import software.amazon.awssdk.services.s3.model.DeleteObjectRequest;
import software.amazon.awssdk.services.s3.model.DeleteObjectsRequest;
import software.amazon.awssdk.services.s3.model.GetObjectRequest;
import software.amazon.awssdk.services.s3.model.ListObjectsV2Request;
import software.amazon.awssdk.services.s3.model.ObjectIdentifier;
import software.amazon.awssdk.services.s3.model.PutObjectRequest;
import software.amazon.awssdk.services.s3.model.S3Object;
import software.amazon.awssdk.services.s3.presigner.S3Presigner;
import software.amazon.awssdk.services.s3.presigner.model.GetObjectPresignRequest;
import software.amazon.awssdk.services.s3.presigner.model.PresignedGetObjectRequest;

import java.io.IOException;
import java.nio.file.Files;
import java.nio.file.Path;
import java.time.Duration;
import java.time.ZoneId;
import java.time.format.DateTimeFormatter;
import java.util.ArrayList;
import java.util.Comparator;
import java.util.List;

@Service
public class B2StorageService {

    private static final DateTimeFormatter DATE_FORMATTER = DateTimeFormatter.ofPattern("dd/MM/yyyy HH:mm")
            .withZone(ZoneId.systemDefault());

    private final ObjectProvider<S3Client> s3ClientProvider;
    private final ObjectProvider<S3Presigner> s3PresignerProvider;
    private final boolean enabled;
    private final String bucketName;
    private final String endpoint;
    private final long presignDurationMinutes;

    public B2StorageService(ObjectProvider<S3Client> s3ClientProvider,
                            ObjectProvider<S3Presigner> s3PresignerProvider,
                            @Value("${app.storage.b2.enabled:false}") boolean enabled,
                            @Value("${app.storage.b2.bucket-name:}") String bucketName,
                            @Value("${app.storage.b2.endpoint:}") String endpoint,
                            @Value("${app.storage.b2.presign-duration-minutes:15}") long presignDurationMinutes) {
        this.s3ClientProvider = s3ClientProvider;
        this.s3PresignerProvider = s3PresignerProvider;
        this.enabled = enabled;
        this.bucketName = bucketName;
        this.endpoint = endpoint;
        this.presignDurationMinutes = presignDurationMinutes;
    }

    // ===== FOLDER NAVIGATION =====

    public FolderView listFolder(String prefix) {
        if (!enabled) {
            return FolderView.disabled(bucketName, endpoint,
                    "L'integration Backblaze B2 est desactivee. Positionnez app.storage.b2.enabled=true et renseignez les credentials.");
        }
        if (bucketName == null || bucketName.isBlank()) {
            return FolderView.disabled("", endpoint, "Le nom du bucket Backblaze B2 n'est pas configure.");
        }
        S3Client s3Client = s3ClientProvider.getIfAvailable();
        S3Presigner presigner = s3PresignerProvider.getIfAvailable();
        if (s3Client == null || presigner == null) {
            return FolderView.disabled(bucketName, endpoint,
                    "Les clients S3 ne sont pas initialises. Verifiez la configuration Spring Cloud AWS et les credentials B2.");
        }

        String normalizedPrefix = (prefix == null || prefix.isBlank()) ? ""
                : (prefix.endsWith("/") ? prefix : prefix + "/");

        try {
            List<FolderItem> subfolders = new ArrayList<>();
            List<S3FileItem> files = new ArrayList<>();
            String continuationToken = null;

            do {
                var builder = ListObjectsV2Request.builder()
                        .bucket(bucketName)
                        .delimiter("/")
                        .maxKeys(1000);
                if (!normalizedPrefix.isEmpty()) builder.prefix(normalizedPrefix);
                if (continuationToken != null) builder.continuationToken(continuationToken);
                var response = s3Client.listObjectsV2(builder.build());

                for (CommonPrefix cp : response.commonPrefixes()) {
                    String folderPrefix = cp.prefix();
                    String folderName = extractFolderName(folderPrefix);
                    subfolders.add(new FolderItem(folderPrefix, folderName));
                }
                for (S3Object obj : response.contents()) {
                    if (!obj.key().equals(normalizedPrefix)) {
                        files.add(toItem(obj, presigner));
                    }
                }
                continuationToken = response.isTruncated() ? response.nextContinuationToken() : null;
            } while (continuationToken != null);

            files.sort(Comparator.comparing(S3FileItem::lastModifiedRaw,
                    Comparator.nullsLast(Comparator.reverseOrder())));
            return FolderView.ready(bucketName, endpoint, normalizedPrefix, subfolders, files, presignDurationMinutes);
        } catch (Exception e) {
            return FolderView.disabled(bucketName, endpoint,
                    "Impossible de lire le bucket B2: " + e.getMessage());
        }
    }

    public void createFolder(String folderPath) {
        S3Client s3Client = s3ClientProvider.getIfAvailable();
        if (s3Client == null) throw new IllegalStateException("S3 client non disponible");
        String key = folderPath.endsWith("/") ? folderPath : folderPath + "/";
        s3Client.putObject(
                PutObjectRequest.builder().bucket(bucketName).key(key).contentLength(0L).build(),
                RequestBody.empty());
    }

    public void uploadFile(String key, MultipartFile file) throws IOException {
        S3Client s3Client = s3ClientProvider.getIfAvailable();
        if (s3Client == null) throw new IllegalStateException("S3 client non disponible");
        String contentType = file.getContentType() != null ? file.getContentType() : "application/octet-stream";
        s3Client.putObject(
                PutObjectRequest.builder()
                        .bucket(bucketName)
                        .key(key)
                        .contentType(contentType)
                        .contentLength(file.getSize())
                        .build(),
                RequestBody.fromInputStream(file.getInputStream(), file.getSize()));
    }

    public void uploadFile(String key, Path filePath) throws IOException {
        S3Client s3Client = s3ClientProvider.getIfAvailable();
        if (s3Client == null) throw new IllegalStateException("S3 client non disponible");
        String contentType = Files.probeContentType(filePath);
        if (contentType == null || contentType.isBlank()) {
            contentType = "application/octet-stream";
        }
        s3Client.putObject(
                PutObjectRequest.builder()
                        .bucket(bucketName)
                        .key(key)
                        .contentType(contentType)
                        .contentLength(Files.size(filePath))
                        .build(),
                RequestBody.fromFile(filePath));
    }

    public record FileLinks(String fileName, String viewUrl, String downloadUrl) {}

    public FileLinks fileLinks(String key) {
        if (key == null || key.isBlank()) return null;
        S3Presigner presigner = s3PresignerProvider.getIfAvailable();
        if (presigner == null) return null;
        String fileName = extractFileName(key);
        int sep = fileName.indexOf("__");
        String displayName = sep >= 0 ? fileName.substring(sep + 2) : fileName;
        return new FileLinks(displayName, presignUrl(presigner, key, false), presignUrl(presigner, key, true));
    }

    public void deleteObject(String key) {
        S3Client s3Client = s3ClientProvider.getIfAvailable();
        if (s3Client == null) throw new IllegalStateException("S3 client non disponible");
        s3Client.deleteObject(DeleteObjectRequest.builder().bucket(bucketName).key(key).build());
    }

    public int deletePrefix(String prefix) {
        S3Client s3Client = s3ClientProvider.getIfAvailable();
        if (s3Client == null) throw new IllegalStateException("S3 client non disponible");
        String normalized = prefix.endsWith("/") ? prefix : prefix + "/";
        int total = 0;
        String token = null;
        do {
            var req = ListObjectsV2Request.builder()
                    .bucket(bucketName).prefix(normalized).maxKeys(1000);
            if (token != null) req.continuationToken(token);
            var resp = s3Client.listObjectsV2(req.build());
            List<ObjectIdentifier> ids = resp.contents().stream()
                    .map(o -> ObjectIdentifier.builder().key(o.key()).build())
                    .toList();
            if (!ids.isEmpty()) {
                s3Client.deleteObjects(DeleteObjectsRequest.builder()
                        .bucket(bucketName)
                        .delete(Delete.builder().objects(ids).quiet(true).build())
                        .build());
                total += ids.size();
            }
            token = resp.isTruncated() ? resp.nextContinuationToken() : null;
        } while (token != null);
        // delete the folder marker itself if it exists
        try { s3Client.deleteObject(DeleteObjectRequest.builder().bucket(bucketName).key(normalized).build()); }
        catch (Exception ignored) {}
        return total;
    }

    private String extractFolderName(String folderPrefix) {
        String withoutTrailing = folderPrefix.endsWith("/")
                ? folderPrefix.substring(0, folderPrefix.length() - 1)
                : folderPrefix;
        int idx = withoutTrailing.lastIndexOf('/');
        return idx >= 0 ? withoutTrailing.substring(idx + 1) : withoutTrailing;
    }

    // ===== LEGACY FLAT LIST =====

    public BucketView listFiles() {
        if (!enabled) {
            return BucketView.disabled(bucketName, endpoint,
                    "L'integration Backblaze B2 est desactivee. Positionnez app.storage.b2.enabled=true et renseignez les credentials.");
        }
        if (bucketName == null || bucketName.isBlank()) {
            return BucketView.disabled("", endpoint, "Le nom du bucket Backblaze B2 n'est pas configure.");
        }

        S3Client s3Client = s3ClientProvider.getIfAvailable();
        S3Presigner presigner = s3PresignerProvider.getIfAvailable();
        if (s3Client == null || presigner == null) {
            return BucketView.disabled(bucketName, endpoint,
                    "Les clients S3 ne sont pas initialises. Verifiez la configuration Spring Cloud AWS et les credentials B2.");
        }

        try {
            List<S3FileItem> items = new ArrayList<>();
            String continuationToken = null;

            do {
                var requestBuilder = ListObjectsV2Request.builder()
                        .bucket(bucketName)
                        .maxKeys(1000);
                if (continuationToken != null) {
                    requestBuilder.continuationToken(continuationToken);
                }
                var response = s3Client.listObjectsV2(requestBuilder.build());
                for (S3Object object : response.contents()) {
                    items.add(toItem(object, presigner));
                }
                continuationToken = response.isTruncated() ? response.nextContinuationToken() : null;
            } while (continuationToken != null);

            items.sort(Comparator.comparing(S3FileItem::lastModifiedRaw,
                    Comparator.nullsLast(Comparator.reverseOrder())));
            long totalBytes = items.stream().mapToLong(S3FileItem::sizeBytes).sum();
            return BucketView.ready(bucketName, endpoint, items, totalBytes, presignDurationMinutes);
        } catch (Exception e) {
            return BucketView.disabled(bucketName, endpoint,
                    "Impossible de lire le bucket B2: " + e.getMessage());
        }
    }

    private S3FileItem toItem(S3Object object, S3Presigner presigner) {
        String key = object.key();
        return new S3FileItem(
                key,
                extractFileName(key),
                humanSize(object.size()),
                object.size(),
                object.lastModified() != null ? DATE_FORMATTER.format(object.lastModified()) : "—",
                object.lastModified(),
                presignUrl(presigner, key, false),
                presignUrl(presigner, key, true));
    }

    private String presignUrl(S3Presigner presigner, String key, boolean download) {
        GetObjectRequest.Builder getObjectBuilder = GetObjectRequest.builder()
                .bucket(bucketName)
                .key(key);
        if (download) {
            getObjectBuilder.responseContentDisposition("attachment; filename=\"" + extractFileName(key) + "\"");
        }
        PresignedGetObjectRequest request = presigner.presignGetObject(GetObjectPresignRequest.builder()
                .signatureDuration(Duration.ofMinutes(Math.max(1, presignDurationMinutes)))
                .getObjectRequest(getObjectBuilder.build())
                .build());
        return request.url().toString();
    }

    private String extractFileName(String key) {
        int index = key.lastIndexOf('/');
        return index >= 0 ? key.substring(index + 1) : key;
    }

    private static String humanSize(long bytes) {
        if (bytes < 1024) {
            return bytes + " B";
        }
        double kb = bytes / 1024.0;
        if (kb < 1024) {
            return String.format(java.util.Locale.US, "%.1f KB", kb);
        }
        double mb = kb / 1024.0;
        if (mb < 1024) {
            return String.format(java.util.Locale.US, "%.1f MB", mb);
        }
        double gb = mb / 1024.0;
        return String.format(java.util.Locale.US, "%.2f GB", gb);
    }

        public record BucketView(boolean enabled,
                             String bucketName,
                             String endpoint,
                             String message,
                             List<S3FileItem> items,
                             long totalBytes,
                             String totalSizeLabel,
                             long presignDurationMinutes) {

        static BucketView ready(String bucketName, String endpoint, List<S3FileItem> items, long totalBytes, long presignDurationMinutes) {
            String totalSizeLabel = humanSize(totalBytes);
            return new BucketView(true, bucketName, endpoint, "", items, totalBytes, totalSizeLabel, presignDurationMinutes);
        }

        static BucketView disabled(String bucketName, String endpoint, String message) {
            return new BucketView(false, bucketName, endpoint, message, List.of(), 0L, "0 B", 0L);
        }
    }

    public record S3FileItem(String key,
                             String fileName,
                             String sizeLabel,
                             long sizeBytes,
                             String lastModifiedLabel,
                             java.time.Instant lastModifiedRaw,
                             String viewUrl,
                             String downloadUrl) {
    }

    public record FolderItem(String prefix, String name) {}

    public record FolderView(boolean enabled,
                             String bucketName,
                             String endpoint,
                             String message,
                             String currentPrefix,
                             List<FolderItem> subfolders,
                             List<S3FileItem> files,
                             long totalBytes,
                             String totalSizeLabel,
                             long presignDurationMinutes) {

        static FolderView ready(String bucketName, String endpoint, String currentPrefix,
                                List<FolderItem> subfolders, List<S3FileItem> files, long presignDurationMinutes) {
            long totalBytes = files.stream().mapToLong(S3FileItem::sizeBytes).sum();
            return new FolderView(true, bucketName, endpoint, "", currentPrefix,
                    subfolders, files, totalBytes, humanSize(totalBytes), presignDurationMinutes);
        }

        static FolderView disabled(String bucketName, String endpoint, String message) {
            return new FolderView(false, bucketName, endpoint, message, "",
                    List.of(), List.of(), 0L, "0 B", 0L);
        }
    }
}
