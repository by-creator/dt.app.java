package com.dtapp.controller;

import com.dtapp.entity.User;
import com.dtapp.repository.UserRepository;
import org.springframework.security.core.Authentication;
import org.springframework.security.core.GrantedAuthority;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.servlet.mvc.support.RedirectAttributes;

import java.util.Set;
import java.util.stream.Collectors;

@Controller
@RequestMapping("/parametres")
public class ParametresController {

    private final UserRepository userRepository;
    private final PasswordEncoder passwordEncoder;

    public ParametresController(UserRepository userRepository,
                                PasswordEncoder passwordEncoder) {
        this.userRepository  = userRepository;
        this.passwordEncoder = passwordEncoder;
    }

    @GetMapping
    public String parametres(Model model, Authentication auth) {
        Set<String> roles = auth.getAuthorities().stream()
                .map(GrantedAuthority::getAuthority).collect(Collectors.toSet());
        model.addAttribute("loggedUser", loggedUser(auth));
        model.addAttribute("menuUrl", resolveMenuUrl(roles));
        return "parametres";
    }

    private String resolveMenuUrl(Set<String> roles) {
        if (roles.contains("ROLE_ADMIN"))                  return "/menu";
        if (roles.contains("ROLE_FACTURATION"))            return "/menu/facturation";
        if (roles.contains("ROLE_DIRECTION_GENERALE"))     return "/menu/direction-generale";
        if (roles.contains("ROLE_DIRECTION_FINANCIERE"))   return "/menu/direction-financiere";
        if (roles.contains("ROLE_DIRECTION_EXPLOITATION")) return "/menu/direction-exploitation";
        if (roles.contains("ROLE_PLANIFICATION"))          return "/menu/planification";
        return "/menu";
    }

    @PostMapping("/profil")
    public String updateProfil(@RequestParam String username,
                               Authentication auth,
                               RedirectAttributes ra) {
        if (username == null || username.isBlank()) {
            ra.addFlashAttribute("profilError", "Le nom d'affichage ne peut pas être vide.");
            return "redirect:/parametres";
        }
        User user = loggedUser(auth);
        user.setUsername(username.trim());
        userRepository.save(user);
        ra.addFlashAttribute("profilSuccess", "Profil mis à jour avec succès.");
        return "redirect:/parametres";
    }

    @PostMapping("/password")
    public String updatePassword(@RequestParam String currentPassword,
                                 @RequestParam String newPassword,
                                 @RequestParam String confirmPassword,
                                 Authentication auth,
                                 RedirectAttributes ra) {
        User user = loggedUser(auth);

        if (!passwordEncoder.matches(currentPassword, user.getPassword())) {
            ra.addFlashAttribute("passwordError", "Le mot de passe actuel est incorrect.");
            return "redirect:/parametres";
        }
        if (!newPassword.equals(confirmPassword)) {
            ra.addFlashAttribute("passwordError", "Les nouveaux mots de passe ne correspondent pas.");
            return "redirect:/parametres";
        }
        if (newPassword.length() < 8) {
            ra.addFlashAttribute("passwordError", "Le nouveau mot de passe doit contenir au moins 8 caractères.");
            return "redirect:/parametres";
        }
        user.setPassword(passwordEncoder.encode(newPassword));
        userRepository.save(user);
        ra.addFlashAttribute("passwordSuccess", "Mot de passe modifié avec succès.");
        return "redirect:/parametres";
    }

    private User loggedUser(Authentication auth) {
        return userRepository.findByEmail(auth.getName()).orElseThrow();
    }
}
