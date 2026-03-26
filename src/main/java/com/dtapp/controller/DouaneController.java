package com.dtapp.controller;

import com.dtapp.entity.BlocageItem;
import com.dtapp.entity.User;
import com.dtapp.repository.BlocageItemRepository;
import com.dtapp.repository.UserRepository;
import org.springframework.security.core.Authentication;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.servlet.mvc.support.RedirectAttributes;

import java.util.List;

@Controller
@RequestMapping("/menu/douane")
public class DouaneController {

    private final UserRepository userRepository;
    private final BlocageItemRepository blocageItemRepository;

    public DouaneController(UserRepository userRepository,
                            BlocageItemRepository blocageItemRepository) {
        this.userRepository       = userRepository;
        this.blocageItemRepository = blocageItemRepository;
    }

    @GetMapping
    public String index(Model model, Authentication auth) {
        model.addAttribute("loggedUser", loggedUser(auth));
        return "douane/index";
    }

    @GetMapping("/blocage")
    public String blocage(@RequestParam(required = false) String search,
                          Model model, Authentication auth) {
        model.addAttribute("loggedUser", loggedUser(auth));
        List<BlocageItem> items = blocageItemRepository.searchAll(search);
        model.addAttribute("blocageItems", items);
        model.addAttribute("search", search != null ? search : "");
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

        String itemTrimmed = item.trim();
        BlocageItem entry = blocageItemRepository
                .findTopByItemOrderByCreatedAtDesc(itemTrimmed)
                .orElse(null);

        if ("bloquer".equals(action)) {
            if (entry != null && entry.isBloque()) {
                ra.addFlashAttribute("error",
                        "L'item « " + itemTrimmed + " » est déjà bloqué.");
            } else if (entry != null) {
                entry.setStatut("BLOQUE");
                blocageItemRepository.save(entry);
                ra.addFlashAttribute("success",
                        "L'item « " + itemTrimmed + " » a été bloqué.");
            } else {
                BlocageItem newEntry = new BlocageItem();
                newEntry.setItem(itemTrimmed);
                newEntry.setStatut("BLOQUE");
                blocageItemRepository.save(newEntry);
                ra.addFlashAttribute("success",
                        "L'item « " + itemTrimmed + " » a été bloqué.");
            }
        } else if ("debloquer".equals(action)) {
            if (entry == null || !entry.isBloque()) {
                ra.addFlashAttribute("error",
                        "L'item « " + itemTrimmed + " » n'est pas bloqué.");
            } else {
                entry.setStatut("DEBLOQUE");
                blocageItemRepository.save(entry);
                ra.addFlashAttribute("success",
                        "L'item « " + itemTrimmed + " » a été débloqué.");
            }
        }

        return "redirect:/menu/douane/blocage";
    }

    @PostMapping("/blocage/action")
    public String tableAction(@RequestParam Integer id,
                              @RequestParam String action,
                              RedirectAttributes ra) {
        blocageItemRepository.findById(id).ifPresent(entry -> {
            if ("bloquer".equals(action)) {
                entry.setStatut("BLOQUE");
            } else if ("debloquer".equals(action)) {
                entry.setStatut("DEBLOQUE");
            }
            blocageItemRepository.save(entry);
            ra.addFlashAttribute("success",
                    "Statut de « " + entry.getItem() + " » mis à jour.");
        });
        return "redirect:/menu/douane/blocage";
    }

    private User loggedUser(Authentication auth) {
        return userRepository.findByEmail(auth.getName()).orElseThrow();
    }
}
