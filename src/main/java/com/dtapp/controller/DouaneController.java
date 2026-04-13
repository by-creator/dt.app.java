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
    private static final int MAX_TABLE_ROWS = 100000;

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
                          @RequestParam(required = false) String filterItem,
                          @RequestParam(required = false) String filterStatut,
                          @RequestParam(required = false) String filterDate,
                          @RequestParam(defaultValue = "0") int page,
                          Model model, Authentication auth) {

        boolean hasColumnFilter = hasText(filterItem) || hasText(filterStatut) || hasText(filterDate);

        Page<BlocageItem> itemsPage;
        if (hasColumnFilter) {
            itemsPage = blocageItemRepository.filterByColumns(
                    emptyNull(filterItem), emptyNull(filterStatut), emptyNull(filterDate),
                    PageRequest.of(0, MAX_TABLE_ROWS));
        } else {
            itemsPage = blocageItemRepository.searchPaged(search, PageRequest.of(0, MAX_TABLE_ROWS));
        }

        model.addAttribute("loggedUser",    loggedUser(auth));
        model.addAttribute("blocageItems",  itemsPage.getContent());
        model.addAttribute("search",        search       != null ? search       : "");
        model.addAttribute("filterItem",    filterItem   != null ? filterItem   : "");
        model.addAttribute("filterStatut",  filterStatut != null ? filterStatut : "");
        model.addAttribute("filterDate",    filterDate   != null ? filterDate   : "");
        model.addAttribute("currentPage",   0);
        model.addAttribute("totalPages",    0);
        model.addAttribute("totalItems",    itemsPage.getTotalElements());
        model.addAttribute("pageSize",      itemsPage.getContent().size());
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
    public String tableAction(@RequestParam int id,
                              @RequestParam String action,
                              @RequestParam(defaultValue = "0") int page,
                              @RequestParam(required = false) String filterItem,
                              @RequestParam(required = false) String filterStatut,
                              @RequestParam(required = false) String filterDate,
                              RedirectAttributes ra) {
        blocageItemRepository.findById(id).ifPresent(entry -> {
            entry.setStatut("bloquer".equals(action) ? "BLOQUE" : "DEBLOQUE");
            blocageItemRepository.save(entry);
            ra.addFlashAttribute("success", "Statut de « " + entry.getItem() + " » mis à jour.");
        });
        StringBuilder redirect = new StringBuilder("redirect:/menu/douane/blocage");
        if (filterItem   != null && !filterItem.isBlank())   redirect.append("&filterItem=").append(filterItem);
        if (filterStatut != null && !filterStatut.isBlank()) redirect.append("&filterStatut=").append(filterStatut);
        if (filterDate   != null && !filterDate.isBlank())   redirect.append("&filterDate=").append(filterDate);
        return redirect.toString();
    }

    private User loggedUser(Authentication auth) {
        return userRepository.findByEmail(auth.getName()).orElseThrow();
    }

    private boolean hasText(String s) {
        return s != null && !s.isBlank();
    }

    private String emptyNull(String s) {
        return (s == null || s.isBlank()) ? "" : s.trim();
    }
}
