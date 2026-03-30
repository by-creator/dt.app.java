package com.dtapp.config;

import com.dtapp.entity.AuditLog;
import com.dtapp.entity.User;
import com.dtapp.repository.AuditLogRepository;
import com.dtapp.repository.UserRepository;
import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.databind.ObjectMapper;
import jakarta.servlet.FilterChain;
import jakarta.servlet.ServletException;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import jakarta.servlet.http.HttpSession;
import org.springframework.security.authentication.AnonymousAuthenticationToken;
import org.springframework.security.core.Authentication;
import org.springframework.security.core.context.SecurityContextHolder;
import org.springframework.lang.NonNull;
import org.springframework.stereotype.Component;
import org.springframework.web.filter.OncePerRequestFilter;
import org.springframework.web.servlet.HandlerMapping;
import org.springframework.web.util.ContentCachingRequestWrapper;
import org.springframework.web.util.ContentCachingResponseWrapper;

import java.io.IOException;
import java.nio.charset.StandardCharsets;
import java.util.Arrays;
import java.util.LinkedHashMap;
import java.util.Map;
import java.util.Optional;
import java.util.stream.Collectors;

@Component
public class AuditLogFilter extends OncePerRequestFilter {

    private final AuditLogRepository auditLogRepository;
    private final UserRepository userRepository;
    private final ObjectMapper objectMapper;

    public AuditLogFilter(AuditLogRepository auditLogRepository,
                          UserRepository userRepository,
                          ObjectMapper objectMapper) {
        this.auditLogRepository = auditLogRepository;
        this.userRepository = userRepository;
        this.objectMapper = objectMapper;
    }

    @Override
    protected boolean shouldNotFilter(@NonNull HttpServletRequest request) {
        String uri = request.getRequestURI();
        return uri.startsWith("/css/")
                || uri.startsWith("/js/")
                || uri.startsWith("/img/")
                || uri.startsWith("/images/")
                || uri.startsWith("/webjars/")
                || uri.startsWith("/actuator")
                || uri.equals("/favicon.ico");
    }

    @Override
    protected void doFilterInternal(@NonNull HttpServletRequest request,
                                    @NonNull HttpServletResponse response,
                                    @NonNull FilterChain filterChain) throws ServletException, IOException {
        long startedAt = System.currentTimeMillis();
        ContentCachingRequestWrapper wrappedRequest = new ContentCachingRequestWrapper(request);
        ContentCachingResponseWrapper wrappedResponse = new ContentCachingResponseWrapper(response);

        try {
            filterChain.doFilter(wrappedRequest, wrappedResponse);
        } finally {
            persistAuditLog(wrappedRequest, wrappedResponse, startedAt);
            wrappedResponse.copyBodyToResponse();
        }
    }

    private void persistAuditLog(ContentCachingRequestWrapper request,
                                 ContentCachingResponseWrapper response,
                                 long startedAt) {
        try {
            AuditLog log = new AuditLog();
            Authentication authentication = SecurityContextHolder.getContext().getAuthentication();
            Optional<User> connectedUser = resolveUser(authentication);

            connectedUser.ifPresent(user -> {
                log.setUserId(user.getId() != null ? user.getId().longValue() : null);
                log.setUserName(user.getUsername());
                log.setUserEmail(user.getEmail());
            });

            if (authentication != null && authentication.isAuthenticated()
                    && !(authentication instanceof AnonymousAuthenticationToken)) {
                String role = authentication.getAuthorities().stream()
                        .map(grantedAuthority -> grantedAuthority.getAuthority())
                        .sorted()
                        .collect(Collectors.joining(", "));
                log.setUserRole(role.isBlank() ? null : role);
            }

            log.setMethod(request.getMethod());
            log.setUrl(buildFullUrl(request));
            log.setRouteName(resolveAttribute(request, HandlerMapping.BEST_MATCHING_PATTERN_ATTRIBUTE));
            log.setControllerAction(resolveControllerAction(request));
            log.setIpAddress(extractIpAddress(request));
            log.setUserAgent(request.getHeader("User-Agent"));
            log.setPayload(extractPayload(request));
            log.setQueryParams(extractQueryParams(request));

            HttpSession session = request.getSession(false);
            log.setSessionId(session != null ? session.getId() : null);
            log.setResponseStatus((short) response.getStatus());
            log.setDurationMs(Math.toIntExact(Math.max(System.currentTimeMillis() - startedAt, 0)));

            auditLogRepository.save(log);
        } catch (Exception ignored) {
            // Avoid breaking business requests if audit persistence fails.
        }
    }

    private Optional<User> resolveUser(Authentication authentication) {
        if (authentication == null || !authentication.isAuthenticated()
                || authentication instanceof AnonymousAuthenticationToken) {
            return Optional.empty();
        }
        return userRepository.findByEmail(authentication.getName());
    }

    private String buildFullUrl(HttpServletRequest request) {
        StringBuilder builder = new StringBuilder(request.getRequestURL());
        if (request.getQueryString() != null && !request.getQueryString().isBlank()) {
            builder.append('?').append(request.getQueryString());
        }
        return builder.toString();
    }

    private String resolveControllerAction(HttpServletRequest request) {
        Object handler = request.getAttribute(HandlerMapping.BEST_MATCHING_HANDLER_ATTRIBUTE);
        return handler != null ? handler.toString() : null;
    }

    private String resolveAttribute(HttpServletRequest request, String key) {
        Object value = request.getAttribute(key);
        return value != null ? value.toString() : null;
    }

    private String extractIpAddress(HttpServletRequest request) {
        String forwardedFor = request.getHeader("X-Forwarded-For");
        if (forwardedFor != null && !forwardedFor.isBlank()) {
            return forwardedFor.split(",")[0].trim();
        }
        return request.getRemoteAddr();
    }

    private String extractPayload(ContentCachingRequestWrapper request) {
        String contentType = request.getContentType();
        if (contentType != null && contentType.toLowerCase().contains("multipart/form-data")) {
            return null;
        }

        byte[] content = request.getContentAsByteArray();
        if (content.length == 0) {
            return null;
        }

        String body = new String(content, StandardCharsets.UTF_8).trim();
        if (body.isBlank()) {
            return null;
        }

        if (body.length() > 8000) {
            body = body.substring(0, 8000);
        }

        if (body.startsWith("{") || body.startsWith("[")) {
            return body;
        }

        Map<String, Object> wrapper = new LinkedHashMap<>();
        wrapper.put("raw", body);
        return toJson(wrapper);
    }

    private String extractQueryParams(HttpServletRequest request) {
        if (request.getParameterMap().isEmpty()) {
            return null;
        }

        Map<String, Object> params = new LinkedHashMap<>();
        request.getParameterMap().forEach((key, value) -> {
            if (value == null) {
                params.put(key, null);
            } else if (value.length == 1) {
                params.put(key, value[0]);
            } else {
                params.put(key, Arrays.asList(value));
            }
        });
        return toJson(params);
    }

    private String toJson(Map<String, Object> value) {
        try {
            return objectMapper.writeValueAsString(value);
        } catch (JsonProcessingException e) {
            return null;
        }
    }
}
