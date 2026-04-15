package com.dtapp.controller;

import com.dtapp.repository.AuditLogRepository;
import com.dtapp.repository.UserRepository;
import com.dtapp.util.PaginationUtils;
import org.springframework.data.domain.Page;
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
                        @RequestParam(defaultValue = "25") int size,
                        Model model,
                        Authentication auth) {
        var pageable = PaginationUtils.pageable(page, size, Sort.by(Sort.Direction.DESC, "createdAt"));

        Page<com.dtapp.entity.AuditLog> auditPage = auditLogRepository.searchWithFilters(
                search, filterMethode, filterDate, pageable);

        model.addAttribute("loggedUser",     userRepository.findByEmail(auth.getName()).orElseThrow());
        model.addAttribute("auditLogs",      auditPage.getContent());
        model.addAttribute("search",         search         != null ? search         : "");
        model.addAttribute("filterDate",     filterDate     != null ? filterDate     : "");
        model.addAttribute("filterMethode",  filterMethode  != null ? filterMethode  : "");
        PaginationUtils.addPageAttributes(model, auditPage);
        return "audit/index";
    }
}
