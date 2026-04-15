package com.dtapp.config;

import org.springframework.beans.factory.annotation.Value;
import org.springframework.boot.autoconfigure.condition.ConditionalOnProperty;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import software.amazon.awssdk.auth.credentials.AwsBasicCredentials;
import software.amazon.awssdk.auth.credentials.StaticCredentialsProvider;
import software.amazon.awssdk.regions.Region;
import software.amazon.awssdk.services.s3.presigner.S3Presigner;
import software.amazon.awssdk.services.s3.presigner.S3Presigner.Builder;

import java.net.URI;

@Configuration
@ConditionalOnProperty(prefix = "app.storage.b2", name = "enabled", havingValue = "true")
public class BackblazeB2Config {

    @Bean
    public S3Presigner s3Presigner(@Value("${spring.cloud.aws.credentials.access-key}") String accessKey,
                                   @Value("${spring.cloud.aws.credentials.secret-key}") String secretKey,
                                   @Value("${spring.cloud.aws.region.static}") String region,
                                   @Value("${app.storage.b2.endpoint}") String endpoint) {
        Builder builder = S3Presigner.builder()
                .region(Region.of(region))
                .credentialsProvider(StaticCredentialsProvider.create(AwsBasicCredentials.create(accessKey, secretKey)))
                .endpointOverride(URI.create(endpoint));
        return builder.build();
    }
}
