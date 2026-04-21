import java.io.IOException;
import java.nio.file.Files;
import java.nio.file.Path;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.util.HashMap;
import java.util.Map;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

public class UpdateIesAccountFallback {
    private static final Pattern PLACEHOLDER = Pattern.compile("\\$\\{([^:}]+)(?::([^}]*))?}");

    public static void main(String[] args) throws Exception {
        if (args.length < 2) {
            System.err.println("Usage: UpdateIesAccountFallback <compte> <statut>");
            System.exit(1);
        }

        String compte = args[0].trim();
        String statut = args[1].trim();
        Path propertiesPath = Path.of(args.length >= 3 ? args[2] : "src/main/resources/application.properties");
        Map<String, String> properties = loadProperties(propertiesPath);

        String jdbcUrl = resolve(properties.getOrDefault("spring.datasource.url", ""), System.getenv());
        String username = resolve(properties.getOrDefault("spring.datasource.username", ""), System.getenv());
        String password = resolve(properties.getOrDefault("spring.datasource.password", ""), System.getenv());

        if (jdbcUrl.isBlank()) {
            throw new IllegalStateException("spring.datasource.url introuvable");
        }

        try (Connection connection = DriverManager.getConnection(jdbcUrl, username, password);
             PreparedStatement statement = connection.prepareStatement(
                     "INSERT INTO update_ies_accounts (compte, statut, created_at, updated_at) VALUES (?, ?, NOW(), NOW())")) {
            statement.setString(1, compte);
            statement.setString(2, statut);
            statement.executeUpdate();
        }
    }

    private static Map<String, String> loadProperties(Path path) throws IOException {
        Map<String, String> properties = new HashMap<>();
        for (String rawLine : Files.readAllLines(path)) {
            String line = rawLine.trim();
            if (line.isEmpty() || line.startsWith("#") || !line.contains("=")) {
                continue;
            }
            String[] parts = line.split("=", 2);
            properties.put(parts[0].trim(), parts[1].trim());
        }
        return properties;
    }

    private static String resolve(String rawValue, Map<String, String> env) {
        Matcher matcher = PLACEHOLDER.matcher(rawValue);
        if (!matcher.matches()) {
            return rawValue;
        }
        String envName = matcher.group(1);
        String defaultValue = matcher.group(2) != null ? matcher.group(2) : "";
        return env.getOrDefault(envName, defaultValue);
    }
}
