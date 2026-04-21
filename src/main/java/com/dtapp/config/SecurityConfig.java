package com.dtapp.config;

import com.dtapp.service.UserDetailsServiceImpl;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import org.springframework.security.authentication.dao.DaoAuthenticationProvider;
import org.springframework.security.config.Customizer;
import org.springframework.security.config.annotation.method.configuration.EnableMethodSecurity;
import org.springframework.security.config.annotation.web.builders.HttpSecurity;
import org.springframework.security.config.annotation.web.configuration.EnableWebSecurity;
import org.springframework.security.crypto.bcrypt.BCryptPasswordEncoder;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.security.web.SecurityFilterChain;
import org.springframework.security.web.authentication.AuthenticationSuccessHandler;
import org.springframework.security.web.header.writers.XXssProtectionHeaderWriter;
import org.springframework.security.web.util.matcher.RegexRequestMatcher;

@Configuration
@EnableWebSecurity
@EnableMethodSecurity
public class SecurityConfig {

    private final UserDetailsServiceImpl userDetailsService;
    private final AuditLogFilter auditLogFilter;

    @Value("${app.security.remember-me-key:dt-app-remember-me-key-change-in-prod}")
    private String rememberMeKey;

    public SecurityConfig(UserDetailsServiceImpl userDetailsService,
                          AuditLogFilter auditLogFilter) {
        this.userDetailsService = userDetailsService;
        this.auditLogFilter = auditLogFilter;
    }

    @Bean
    public SecurityFilterChain filterChain(HttpSecurity http) throws Exception {
        http
            .headers(headers -> headers
                .frameOptions(frame -> frame.sameOrigin())
                .xssProtection(xss -> xss.headerValue(XXssProtectionHeaderWriter.HeaderValue.ENABLED_MODE_BLOCK))
                .contentTypeOptions(Customizer.withDefaults())
                .httpStrictTransportSecurity(hsts -> hsts
                    .includeSubDomains(true)
                    .maxAgeInSeconds(31536000)
                )
                .contentSecurityPolicy(csp -> csp.policyDirectives(
                    "default-src 'self'; " +
                    "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; " +
                    "style-src 'self' 'unsafe-inline'; " +
                    "img-src 'self' data: blob:; " +
                    "font-src 'self'; " +
                    "frame-src 'self'; " +
                    "connect-src 'self' wss:; " +
                    "object-src 'none';"
                ))
            )
            .sessionManagement(session -> session
                .maximumSessions(5)
                .maxSessionsPreventsLogin(false)
            )
            .exceptionHandling(ex -> ex
                .defaultAuthenticationEntryPointFor(
                    (request, response, e) -> {
                        response.setStatus(401);
                        response.setContentType("application/json;charset=UTF-8");
                        response.getWriter().write("{\"error\":\"Session expir\\u00e9e, veuillez recharger la page.\"}");
                    },
                    new RegexRequestMatcher("/api/.*", null)
                )
            )
            .authenticationProvider(authenticationProvider())
            .authorizeHttpRequests(authz -> authz
                .requestMatchers("/login", "/css/**", "/js/**", "/img/**",
                                 "/gfa/display", "/gfa/ticket/**", "/api/gfa/display/state", "/demat/**",
                                 "/escale-code-barres", "/escale-code-barres/**",
                                 "/api/ies/accounts/not-found",
                                 "/images/**", "/actuator/health", "/actuator/health/**",
                                 "/api/health/**").permitAll()
                .anyRequest().authenticated()
            )
            .addFilterAfter(auditLogFilter, org.springframework.security.web.context.SecurityContextHolderFilter.class)
            .formLogin(form -> form
                .loginPage("/login")
                .usernameParameter("email")
                .passwordParameter("password")
                .successHandler(loginSuccessHandler())
                .failureUrl("/login?error")
                .permitAll()
            )
            .logout(logout -> logout
                .logoutUrl("/logout")
                .logoutSuccessUrl("/login?logout")
                .invalidateHttpSession(true)
                .deleteCookies("JSESSIONID", "remember-me")
                .permitAll()
            )
            .rememberMe(rm -> rm
                .key(rememberMeKey)
                .rememberMeParameter("remember-me")
                .tokenValiditySeconds(2 * 3600)
            );

        return http.build();
    }

    @Bean
    public AuthenticationSuccessHandler loginSuccessHandler() {
        return (request, response, authentication) -> {
            boolean isClient = authentication.getAuthorities().stream()
                    .anyMatch(a -> "ROLE_CLIENT".equals(a.getAuthority()));
            response.sendRedirect(request.getContextPath() + (isClient ? "/menu/dt/client" : "/dashboard"));
        };
    }

    @Bean
    public DaoAuthenticationProvider authenticationProvider() {
        DaoAuthenticationProvider provider = new DaoAuthenticationProvider(userDetailsService);
        provider.setPasswordEncoder(passwordEncoder());
        return provider;
    }

    @Bean
    public PasswordEncoder passwordEncoder() {
        return new BCryptPasswordEncoder();
    }
}
