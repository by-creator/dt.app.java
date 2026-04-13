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

    private static final int MAX_TABLE_ROWS = 100000;

    private final AuditLogRepository auditLogRepository;
    private final UserRepository userRepository;

    public AuditController(AuditLogRepository auditLogRepository,
                           UserRepository userRepository) {
        this.auditLogRepository = auditLogRepository;
        this.userRepository = userRepository;
    }

    @GetMapping
    public String index(@RequestParam(required = false) String search,
                        @RequestParam(required = false) String filterDate,
                        @RequestParam(required = false) String filterMethode,
                        @RequestParam(defaultValue = "0") int page,
                        Model model,
                        Authentication auth) {
        var pageable = PageRequest.of(0, MAX_TABLE_ROWS, Sort.by(Sort.Direction.DESC, "createdAt"));

        Page<com.dtapp.entity.AuditLog> auditPage = auditLogRepository.searchWithFilters(
                search, filterMethode, filterDate, pageable);

        model.addAttribute("loggedUser",     userRepository.findByEmail(auth.getName()).orElseThrow());
        model.addAttribute("auditLogs",      auditPage.getContent());
        model.addAttribute("search",         search         != null ? search         : "");
        model.addAttribute("filterDate",     filterDate     != null ? filterDate     : "");
        model.addAttribute("filterMethode",  filterMethode  != null ? filterMethode  : "");
        model.addAttribute("currentPage",    0);
        model.addAttribute("totalPages",     0);
        model.addAttribute("totalItems",     auditPage.getTotalElements());
        model.addAttribute("pageSize",       auditPage.getContent().size());
        return "audit/index";
    }
}
