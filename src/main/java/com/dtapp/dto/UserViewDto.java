package com.dtapp.dto;

import com.dtapp.entity.User;

import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;

public class UserViewDto {

    private final Integer id;
    private final String username;
    private final String email;
    private final String initials;
    private final String authority;
    private final String roleBadgeClass;
    private final Boolean enabled;
    private final LocalDateTime createdAt;

    private static final DateTimeFormatter DISPLAY_FMT =
            DateTimeFormatter.ofPattern("dd/MM/yyyy HH:mm");

    public UserViewDto(User user) {
        this.id = user.getId();
        this.username = user.getUsername();
        this.email = user.getEmail();
        this.enabled = user.getEnabled();
        this.createdAt = user.getCreatedAt();

        String firstAuthority = user.getAuthorities().stream()
                .map(a -> a.getAuthority())
                .findFirst()
                .orElse("");
        this.authority = firstAuthority;
        this.initials = computeInitials(user.getUsername());
        this.roleBadgeClass = computeBadgeClass(firstAuthority);
    }

    private static String computeInitials(String username) {
        if (username == null || username.isBlank()) return "?";
        String[] parts = username.trim().split("\\s+");
        if (parts.length >= 2) {
            return ("" + parts[0].charAt(0) + parts[1].charAt(0)).toUpperCase();
        }
        return username.length() >= 2
                ? username.substring(0, 2).toUpperCase()
                : username.substring(0, 1).toUpperCase();
    }

    private static String computeBadgeClass(String authority) {
        if (authority == null) return "default";
        String upper = authority.toUpperCase();
        if (upper.contains("ADMIN")) return "admin";
        if (upper.contains("DIRECTION")) return "direction";
        if (upper.contains("FACTURATION")) return "facturation";
        if (upper.contains("PLANIFICATION")) return "planification";
        return "default";
    }

    public String getDisplayAuthority() {
        return authority.startsWith("ROLE_") ? authority.substring(5) : authority;
    }

    public String getFormattedDate() {
        return createdAt != null ? createdAt.format(DISPLAY_FMT) : "";
    }

    public Integer getId() { return id; }
    public String getUsername() { return username; }
    public String getEmail() { return email; }
    public String getInitials() { return initials; }
    public String getAuthority() { return authority; }
    public String getRoleBadgeClass() { return roleBadgeClass; }
    public Boolean getEnabled() { return enabled; }
    public LocalDateTime getCreatedAt() { return createdAt; }
}
