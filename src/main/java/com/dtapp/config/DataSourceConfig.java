package com.dtapp.config;

import com.zaxxer.hikari.HikariConfig;
import com.zaxxer.hikari.HikariDataSource;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.boot.autoconfigure.condition.ConditionalOnProperty;
import org.springframework.boot.jdbc.DataSourceBuilder;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import org.springframework.context.annotation.Primary;

import javax.sql.DataSource;

@Configuration
public class DataSourceConfig {

    private static final Logger log = LoggerFactory.getLogger(DataSourceConfig.class);

    @Value("${spring.datasource.url:jdbc:mysql://localhost:3306/dummy}")
    private String url;

    @Value("${spring.datasource.username:root}")
    private String username;

    @Value("${spring.datasource.password:}")
    private String password;

    @Value("${spring.datasource.driver-class-name:com.mysql.cj.jdbc.Driver}")
    private String driverClassName;

    @Primary
    @Bean
    public DataSource dataSource() {
        try {
            log.info("Creating DataSource with URL: {} (credentials hidden)", maskUrl(url));

            HikariConfig config = new HikariConfig();
            config.setJdbcUrl(url);
            config.setUsername(username);
            config.setPassword(password);
            config.setDriverClassName(driverClassName);
            config.setMaximumPoolSize(5);
            config.setMinimumIdle(1);
            config.setConnectionTimeout(2000);
            config.setIdleTimeout(600000);
            config.setMaxLifetime(1800000);
            config.setInitializationFailTimeout(0); // Don't fail on initialization
            config.setAutoCommit(true);

            HikariDataSource dataSource = new HikariDataSource(config);
            log.info("DataSource created successfully");
            return dataSource;

        } catch (Exception e) {
            log.warn("Failed to create DataSource: {}. Application will continue without database access.", e.getMessage());
            // Return a dummy datasource that won't crash the app
            return DataSourceBuilder.create()
                    .url("jdbc:mysql://localhost:3306/dummy")
                    .username("dummy")
                    .password("dummy")
                    .driverClassName("com.mysql.cj.jdbc.Driver")
                    .build();
        }
    }

    private String maskUrl(String url) {
        // Hide sensitive parts of the connection URL
        if (url == null) return "null";
        return url.replaceAll("([?&]password=)[^&]*", "$1***");
    }
}
