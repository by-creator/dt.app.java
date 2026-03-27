package com.dtapp.controller;

import org.springframework.stereotype.Controller;
import org.springframework.web.bind.annotation.GetMapping;

@Controller
public class GfaDisplayController {

    @GetMapping("/gfa/display")
    public String publicDisplay() {
        return "facturation/gfa-display";
    }
}
