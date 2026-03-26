package com.dtapp.controller;

import com.dtapp.entity.User;
import com.dtapp.repository.UserRepository;
import org.springframework.security.core.Authentication;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;

@Controller
@RequestMapping("/menu/compagnie")
public class CompagnieController {

    private final UserRepository userRepository;

    public CompagnieController(UserRepository userRepository) {
        this.userRepository = userRepository;
    }

    @GetMapping
    public String index(Model model, Authentication auth) {
        model.addAttribute("loggedUser", loggedUser(auth));
        return "compagnie/index";
    }

    private User loggedUser(Authentication auth) {
        return userRepository.findByEmail(auth.getName()).orElseThrow();
    }
}
