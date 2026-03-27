package com.dtapp.controller;

import com.dtapp.entity.User;
import com.dtapp.repository.UserRepository;
import org.springframework.security.core.Authentication;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.GetMapping;

@Controller
public class GfaDisplayController {

    private final UserRepository userRepository;

    public GfaDisplayController(UserRepository userRepository) {
        this.userRepository = userRepository;
    }

    @GetMapping("/gfa/display")
    public String publicDisplay() {
        return "facturation/gfa-display";
    }

    @GetMapping("/menu/facturation/gfa/admin")
    public String admin(Model model, Authentication auth) {
        User loggedUser = userRepository.findByEmail(auth.getName()).orElseThrow();
        model.addAttribute("loggedUser", loggedUser);
        return "facturation/gfa-admin";
    }
}
