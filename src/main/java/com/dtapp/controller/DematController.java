package com.dtapp.controller;

import org.springframework.stereotype.Controller;
import org.springframework.web.bind.annotation.GetMapping;

@Controller
public class DematController {

    @GetMapping("/demat")
    public String dematHome() {
        return "demat/index";
    }

    @GetMapping("/demat/validation")
    public String dematValidation() {
        return "demat/validation";
    }

    @GetMapping("/demat/remise")
    public String dematRemise() {
        return "demat/remise";
    }

    @GetMapping("/demat/paiement")
    public String dematPaiement() {
        return "demat/paiement";
    }

    @GetMapping("/demat/satisfaction")
    public String dematSatisfaction() {
        return "demat/satisfaction";
    }
}
