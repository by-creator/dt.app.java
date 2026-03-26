package com.dtapp.controller;

import com.dtapp.entity.User;
import com.dtapp.repository.UserRepository;
import org.springframework.security.core.Authentication;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.GetMapping;

@Controller
public class MenuController {

    private final UserRepository userRepository;

    public MenuController(UserRepository userRepository) {
        this.userRepository = userRepository;
    }

    @GetMapping("/menu")
    public String menu(Model model, Authentication auth) {
        User loggedUser = userRepository.findByEmail(auth.getName()).orElseThrow();
        model.addAttribute("loggedUser", loggedUser);
        return "menu";
    }
}
