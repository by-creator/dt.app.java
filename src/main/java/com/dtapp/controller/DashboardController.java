package com.dtapp.controller;

import com.dtapp.dto.UserViewDto;
import com.dtapp.entity.User;
import com.dtapp.repository.AuthorityRepository;
import com.dtapp.repository.UserRepository;
import org.springframework.security.core.Authentication;
import org.springframework.security.core.GrantedAuthority;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.GetMapping;

import java.time.LocalDate;
import java.time.format.DateTimeFormatter;
import java.util.List;
import java.util.Locale;
import java.util.Set;
import java.util.stream.Collectors;

@Controller
public class DashboardController {

    private final UserRepository userRepository;
    private final AuthorityRepository authorityRepository;

    public DashboardController(UserRepository userRepository,
                               AuthorityRepository authorityRepository) {
        this.userRepository      = userRepository;
        this.authorityRepository = authorityRepository;
    }

    @GetMapping("/dashboard")
    public String dashboard(Model model, Authentication auth) {
        Set<String> roles = auth.getAuthorities().stream()
                .map(GrantedAuthority::getAuthority).collect(Collectors.toSet());

        if (!roles.contains("ROLE_ADMIN")) {
            String redirect = resolveModuleDashboard(roles);
            if (redirect != null) return "redirect:" + redirect;
        }

        User loggedUser = userRepository.findByEmail(auth.getName()).orElseThrow();

        List<UserViewDto> recentUsers = userRepository.findTop5ByOrderByCreatedAtDesc()
                .stream()
                .map(UserViewDto::new)
                .collect(Collectors.toList());

        model.addAttribute("loggedUser",  loggedUser);
        model.addAttribute("activeUsers", userRepository.countByEnabledTrue());
        model.addAttribute("rolesCount",  authorityRepository.countDistinctAuthorities());
        model.addAttribute("adminCount",  authorityRepository.countByAuthority("ROLE_ADMIN"));
        model.addAttribute("todayCount",  userRepository.countCreatedToday());
        model.addAttribute("recentUsers", recentUsers);
        model.addAttribute("currentDate", formatDate());
        model.addAttribute("menuUrl", "/menu");

        return "dashboard";
    }

    private String resolveModuleDashboard(Set<String> roles) {
        if (roles.contains("ROLE_FACTURATION"))          return "/facturation/dashboard";
        if (roles.contains("ROLE_DIRECTION_GENERALE"))   return "/direction-generale/dashboard";
        if (roles.contains("ROLE_DIRECTION_FINANCIERE")) return "/direction-financiere/dashboard";
        if (roles.contains("ROLE_DIRECTION_EXPLOITATION")) return "/direction-exploitation/dashboard";
        if (roles.contains("ROLE_PLANIFICATION"))        return "/planification/dashboard";
        return null;
    }

    private String formatDate() {
        String raw = LocalDate.now()
                .format(DateTimeFormatter.ofPattern("EEEE d MMMM yyyy",
                        new Locale("fr", "FR")));
        return raw.substring(0, 1).toUpperCase() + raw.substring(1);
    }
}
