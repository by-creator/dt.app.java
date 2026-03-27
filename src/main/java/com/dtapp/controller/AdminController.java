package com.dtapp.controller;

import com.dtapp.entity.Authority;
import com.dtapp.entity.Compagnie;
import com.dtapp.entity.User;
import com.dtapp.repository.AuthorityRepository;
import com.dtapp.repository.CompagnieRepository;
import com.dtapp.repository.UserRepository;
import lombok.extern.slf4j.Slf4j;
import org.springframework.data.domain.Sort;
import org.springframework.security.core.Authentication;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.stereotype.Controller;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.servlet.mvc.support.RedirectAttributes;

@Controller
@RequestMapping("/admin")
@Slf4j
public class AdminController {

    private final UserRepository userRepository;
    private final CompagnieRepository compagnieRepository;
    private final AuthorityRepository authorityRepository;
    private final PasswordEncoder passwordEncoder;

    public AdminController(UserRepository userRepository,
                           CompagnieRepository compagnieRepository,
                           AuthorityRepository authorityRepository,
                           PasswordEncoder passwordEncoder) {
        this.userRepository    = userRepository;
        this.compagnieRepository = compagnieRepository;
        this.authorityRepository = authorityRepository;
        this.passwordEncoder   = passwordEncoder;
    }

    // ===== INDEX =====

    @GetMapping
    public String index(@RequestParam(defaultValue = "users") String tab,
                        Model model, Authentication auth) {
        model.addAttribute("loggedUser", userRepository.findByEmail(auth.getName()).orElseThrow());
        model.addAttribute("users",      userRepository.findAllByOrderByCreatedAtDesc());
        model.addAttribute("compagnies", compagnieRepository.findAll(Sort.by("name")));
        model.addAttribute("authorities", authorityRepository.findAllWithUser());
        model.addAttribute("tab", tab);
        return "admin/index";
    }

    // ===== COMPAGNIES =====

    @PostMapping("/compagnies/create")
    public String createCompagnie(@RequestParam String name, RedirectAttributes ra) {
        name = name.trim();
        if (name.isEmpty()) {
            ra.addFlashAttribute("error", "Le nom de la compagnie est requis.");
            return "redirect:/admin?tab=compagnies";
        }
        if (compagnieRepository.findByName(name).isPresent()) {
            ra.addFlashAttribute("error", "Une compagnie avec ce nom existe déjà.");
            return "redirect:/admin?tab=compagnies";
        }
        Compagnie c = new Compagnie();
        c.setName(name);
        compagnieRepository.save(c);
        ra.addFlashAttribute("success", "Compagnie « " + name + " » créée.");
        return "redirect:/admin?tab=compagnies";
    }

    @PostMapping("/compagnies/{id}/update")
    public String updateCompagnie(@PathVariable Integer id,
                                   @RequestParam String name, RedirectAttributes ra) {
        final String trimmedName = name.trim();
        if (trimmedName.isEmpty()) {
            ra.addFlashAttribute("error", "Le nom est requis.");
            return "redirect:/admin?tab=compagnies";
        }
        compagnieRepository.findById(id).ifPresent(c -> {
            c.setName(trimmedName);
            compagnieRepository.save(c);
        });
        ra.addFlashAttribute("success", "Compagnie mise à jour.");
        return "redirect:/admin?tab=compagnies";
    }

    @PostMapping("/compagnies/{id}/delete")
    @Transactional
    public String deleteCompagnie(@PathVariable Integer id, RedirectAttributes ra) {
        userRepository.unlinkFromCompagnie(id);
        compagnieRepository.deleteById(id);
        ra.addFlashAttribute("success", "Compagnie supprimée.");
        return "redirect:/admin?tab=compagnies";
    }

    // ===== USERS =====

    @PostMapping("/users/create")
    public String createUser(@RequestParam String username,
                             @RequestParam String email,
                             @RequestParam String password,
                             @RequestParam(required = false) String telephone,
                             @RequestParam(required = false) Integer compagnieId,
                             @RequestParam(required = false) String role,
                             RedirectAttributes ra) {
        if (userRepository.findByEmail(email).isPresent()) {
            ra.addFlashAttribute("error", "Cet email est déjà utilisé.");
            return "redirect:/admin?tab=users";
        }
        User user = new User();
        user.setUsername(username.trim());
        user.setEmail(email.trim());
        user.setPassword(passwordEncoder.encode(password));
        user.setTelephone(telephone != null ? telephone.trim() : null);
        user.setEnabled(true);
        if (compagnieId != null) {
            compagnieRepository.findById(compagnieId).ifPresent(user::setCompagnie);
        }
        User savedUser = userRepository.save(user);

        if (role != null && !role.isBlank()) {
            Authority auth = new Authority();
            auth.setUser(savedUser);
            auth.setAuthority(role.trim());
            authorityRepository.save(auth);
        }
        ra.addFlashAttribute("success", "Utilisateur « " + username + " » créé.");
        return "redirect:/admin?tab=users";
    }

    @PostMapping("/users/{id}/update")
    public String updateUser(@PathVariable Integer id,
                             @RequestParam String username,
                             @RequestParam String email,
                             @RequestParam(required = false) String password,
                             @RequestParam(required = false) String telephone,
                             @RequestParam(required = false) Integer compagnieId,
                             RedirectAttributes ra) {
        userRepository.findById(id).ifPresent(user -> {
            user.setUsername(username.trim());
            user.setEmail(email.trim());
            if (password != null && !password.isBlank()) {
                user.setPassword(passwordEncoder.encode(password));
            }
            user.setTelephone(telephone != null ? telephone.trim() : null);
            if (compagnieId != null) {
                compagnieRepository.findById(compagnieId).ifPresent(user::setCompagnie);
            } else {
                user.setCompagnie(null);
            }
            userRepository.save(user);
        });
        ra.addFlashAttribute("success", "Utilisateur mis à jour.");
        return "redirect:/admin?tab=users";
    }

    @PostMapping("/users/{id}/delete")
    public String deleteUser(@PathVariable Integer id, Authentication auth, RedirectAttributes ra) {
        var current = userRepository.findByEmail(auth.getName()).orElseThrow();
        if (current.getId().equals(id)) {
            ra.addFlashAttribute("error", "Vous ne pouvez pas supprimer votre propre compte.");
            return "redirect:/admin?tab=users";
        }
        userRepository.deleteById(id);
        ra.addFlashAttribute("success", "Utilisateur supprimé.");
        return "redirect:/admin?tab=users";
    }

    // ===== AUTHORITIES =====

    @PostMapping("/authorities/create")
    public String createAuthority(@RequestParam Integer userId,
                                  @RequestParam String authority,
                                  RedirectAttributes ra) {
        final String trimmedAuthority = authority.trim();
        if (trimmedAuthority.isEmpty()) {
            ra.addFlashAttribute("error", "Le rôle est requis.");
            return "redirect:/admin?tab=authorities";
        }
        if (authorityRepository.existsByUserIdAndAuthority(userId, trimmedAuthority)) {
            ra.addFlashAttribute("error", "Ce rôle est déjà assigné à cet utilisateur.");
            return "redirect:/admin?tab=authorities";
        }
        userRepository.findById(userId).ifPresent(user -> {
            Authority a = new Authority();
            a.setUser(user);
            a.setAuthority(trimmedAuthority);
            authorityRepository.save(a);
        });
        ra.addFlashAttribute("success", "Rôle assigné.");
        return "redirect:/admin?tab=authorities";
    }

    @PostMapping("/authorities/{id}/delete")
    public String deleteAuthority(@PathVariable Integer id, RedirectAttributes ra) {
        authorityRepository.deleteById(id);
        ra.addFlashAttribute("success", "Rôle supprimé.");
        return "redirect:/admin?tab=authorities";
    }
}
