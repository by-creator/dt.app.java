package com.dtapp.controller;

import com.dtapp.entity.BlocageItem;
import com.dtapp.entity.User;
import com.dtapp.repository.BlocageItemRepository;
import com.dtapp.repository.UserRepository;
import org.springframework.data.domain.Page;
import org.springframework.data.domain.PageRequest;
import org.springframework.security.core.Authentication;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.servlet.mvc.support.RedirectAttributes;

@Controller
@RequestMapping("/menu/douane")
public class DouaneController {

    private static final int PAGE_SIZE = 4;

    private final UserRepository userRepository;
    private final BlocageItemRepository blocageItemRepository;

    public DouaneController(UserRepository userRepository,
                            BlocageItemRepository blocageItemRepository) {
        this.userRepository        = userRepository;
        this.blocageItemRepository = blocageItemRepository;
    }

    @GetMapping
    public String index(Model model, Authentication auth) {
        model.addAttribute("loggedUser", loggedUser(auth));
        return "douane/index";
    }

    @GetMapping("/blocage")
    public String blocage(@RequestParam(required = false) String search,
                          @RequestParam(defaultValue = "0") int page,
                          Model model, Authentication auth) {

        if (page < 0) page = 0;

        Page<BlocageItem> itemsPage = blocageItemRepository.searchPaged(
                search, PageRequest.of(page, PAGE_SIZE));

        /* Clamp page if out of range after a deletion */
        if (page > 0 && page >= itemsPage.getTotalPages()) {
            page = itemsPage.getTotalPages() - 1;
            itemsPage = blocageItemRepository.searchPaged(
                    search, PageRequest.of(Math.max(page, 0), PAGE_SIZE));
        }

        model.addAttribute("loggedUser",  loggedUser(auth));
        model.addAttribute("blocageItems", itemsPage.getContent());
        model.addAttribute("search",       search != null ? search : "");
        model.addAttribute("currentPage",  itemsPage.getNumber());
        model.addAttribute("totalPages",   itemsPage.getTotalPages());
        model.addAttribute("totalItems",   itemsPage.getTotalElements());
        model.addAttribute("pageSize",     PAGE_SIZE);
        return "douane/blocage";
    }

    @PostMapping("/blocage")
    public String handleBlocage(@RequestParam String item,
                                @RequestParam String action,
                                RedirectAttributes ra) {
        if (item == null || item.isBlank()) {
            ra.addFlashAttribute("error", "Veuillez saisir un item.");
            return "redirect:/menu/douane/blocage";
        }

        String trimmed = item.trim();
        BlocageItem entry = blocageItemRepository
                .findTopByItemOrderByCreatedAtDesc(trimmed)
                .orElse(null);

        if ("bloquer".equals(action)) {
            if (entry != null && entry.isBloque()) {
                ra.addFlashAttribute("error", "L'item « " + trimmed + " » est déjà bloqué.");
            } else if (entry != null) {
                entry.setStatut("BLOQUE");
                blocageItemRepository.save(entry);
                ra.addFlashAttribute("success", "L'item « " + trimmed + " » a été bloqué.");
            } else {
                BlocageItem n = new BlocageItem();
                n.setItem(trimmed);
                n.setStatut("BLOQUE");
                blocageItemRepository.save(n);
                ra.addFlashAttribute("success", "L'item « " + trimmed + " » a été bloqué.");
            }
        } else if ("debloquer".equals(action)) {
            if (entry == null || !entry.isBloque()) {
                ra.addFlashAttribute("error", "L'item « " + trimmed + " » n'est pas bloqué.");
            } else {
                entry.setStatut("DEBLOQUE");
                blocageItemRepository.save(entry);
                ra.addFlashAttribute("success", "L'item « " + trimmed + " » a été débloqué.");
            }
        }

        return "redirect:/menu/douane/blocage";
    }

    @PostMapping("/blocage/action")
    public String tableAction(@RequestParam Integer id,
                              @RequestParam String action,
                              @RequestParam(defaultValue = "0") int page,
                              @RequestParam(required = false) String search,
                              RedirectAttributes ra) {
        blocageItemRepository.findById(id).ifPresent(entry -> {
            entry.setStatut("bloquer".equals(action) ? "BLOQUE" : "DEBLOQUE");
            blocageItemRepository.save(entry);
            ra.addFlashAttribute("success", "Statut de « " + entry.getItem() + " » mis à jour.");
        });
        String redirect = "redirect:/menu/douane/blocage?page=" + page;
        if (search != null && !search.isBlank()) redirect += "&search=" + search;
        return redirect;
    }

    private User loggedUser(Authentication auth) {
        return userRepository.findByEmail(auth.getName()).orElseThrow();
    }
}
