package com.dtapp.controller;

import com.dtapp.repository.AuditLogRepository;
import com.dtapp.repository.UserRepository;
import org.springframework.data.domain.Page;
import org.springframework.data.domain.PageRequest;
import org.springframework.data.domain.Sort;
import org.springframework.security.core.Authentication;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;

@Controller
@RequestMapping("/menu/audit")
public class AuditController {

    private static final int PAGE_SIZE = 10;

    private final AuditLogRepository auditLogRepository;
    private final UserRepository userRepository;

    public AuditController(AuditLogRepository auditLogRepository,
                           UserRepository userRepository) {
        this.auditLogRepository = auditLogRepository;
        this.userRepository = userRepository;
    }

    @GetMapping
    public String index(@RequestParam(required = false) String search,
                        @RequestParam(defaultValue = "0") int page,
                        Model model,
                        Authentication auth) {
        int safePage = Math.max(page, 0);
        Page<com.dtapp.entity.AuditLog> auditPage = auditLogRepository.search(
                search,
                PageRequest.of(safePage, PAGE_SIZE, Sort.by(Sort.Direction.DESC, "createdAt"))
        );

        if (safePage > 0 && safePage >= auditPage.getTotalPages() && auditPage.getTotalPages() > 0) {
            safePage = auditPage.getTotalPages() - 1;
            auditPage = auditLogRepository.search(
                    search,
                    PageRequest.of(safePage, PAGE_SIZE, Sort.by(Sort.Direction.DESC, "createdAt"))
            );
        }

        model.addAttribute("loggedUser", userRepository.findByEmail(auth.getName()).orElseThrow());
        model.addAttribute("auditLogs", auditPage.getContent());
        model.addAttribute("search", search != null ? search : "");
        model.addAttribute("currentPage", auditPage.getNumber());
        model.addAttribute("totalPages", auditPage.getTotalPages());
        model.addAttribute("totalItems", auditPage.getTotalElements());
        model.addAttribute("pageSize", PAGE_SIZE);
        return "audit/index";
    }
}
