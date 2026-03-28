package com.dtapp.config;

import com.dtapp.service.UserDetailsServiceImpl;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import org.springframework.security.authentication.dao.DaoAuthenticationProvider;
import org.springframework.security.config.annotation.web.builders.HttpSecurity;
import org.springframework.security.config.annotation.web.configuration.EnableWebSecurity;
import org.springframework.security.crypto.bcrypt.BCryptPasswordEncoder;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.security.web.SecurityFilterChain;

@Configuration
@EnableWebSecurity
public class SecurityConfig {

    private final UserDetailsServiceImpl userDetailsService;
    private final AuditLogFilter auditLogFilter;

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
            )
            .authenticationProvider(authenticationProvider())
            .authorizeHttpRequests(authz -> authz
                .requestMatchers("/login", "/css/**", "/js/**", "/img/**",
                                 "/gfa/display", "/gfa/ticket/**", "/api/gfa/display/state", "/demat/**",
                                 "/images/**", "/actuator/health").permitAll()
                .anyRequest().authenticated()
            )
            .addFilterAfter(auditLogFilter, org.springframework.security.web.context.SecurityContextHolderFilter.class)
            .formLogin(form -> form
                .loginPage("/login")
                .usernameParameter("email")
                .passwordParameter("password")
                .defaultSuccessUrl("/dashboard", true)
                .failureUrl("/login?error")
                .permitAll()
            )
            .logout(logout -> logout
                .logoutUrl("/logout")
                .logoutSuccessUrl("/login?logout")
                .permitAll()
            )
            .rememberMe(rm -> rm
                .key("dt-app-remember-me")
                .rememberMeParameter("remember-me")
                .tokenValiditySeconds(7 * 24 * 3600)
            );

        return http.build();
    }

    @Bean
    public DaoAuthenticationProvider authenticationProvider() {
        DaoAuthenticationProvider provider = new DaoAuthenticationProvider();
        provider.setUserDetailsService(userDetailsService);
        provider.setPasswordEncoder(passwordEncoder());
        return provider;
    }

    @Bean
    public PasswordEncoder passwordEncoder() {
        return new BCryptPasswordEncoder();
    }
}
