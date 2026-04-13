package com.dtapp.controller;

import com.dtapp.entity.Authority;
import com.dtapp.entity.AuthorityDefinition;
import com.dtapp.entity.Compagnie;
import com.dtapp.entity.User;
import com.dtapp.repository.AuditLogRepository;
import com.dtapp.repository.AuthorityDefinitionRepository;
import com.dtapp.repository.AuthorityRepository;
import com.dtapp.repository.CompagnieRepository;
import com.dtapp.repository.UserRepository;
import lombok.extern.slf4j.Slf4j;
import org.apache.poi.ss.usermodel.*;
import org.apache.poi.xssf.usermodel.XSSFWorkbook;
import org.springframework.data.domain.Page;
import org.springframework.data.domain.PageRequest;
import org.springframework.data.domain.Sort;
import org.springframework.http.HttpHeaders;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.beans.factory.annotation.Qualifier;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.security.core.Authentication;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.stereotype.Controller;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.method.HandlerMethod;
import org.springframework.web.multipart.MultipartFile;
import org.springframework.web.servlet.mvc.method.annotation.RequestMappingHandlerMapping;
import org.springframework.web.servlet.mvc.support.RedirectAttributes;

import java.io.*;
import java.nio.charset.StandardCharsets;
import java.util.ArrayList;
import java.util.Comparator;
import java.util.List;
import java.util.stream.Collectors;

@Controller
@RequestMapping("/admin")
@Slf4j
public class AdminController {

    private static final int MAX_TABLE_ROWS = 100000;

    private final UserRepository userRepository;
    private final CompagnieRepository compagnieRepository;
    private final AuthorityRepository authorityRepository;
    private final AuthorityDefinitionRepository authorityDefinitionRepository;
    private final AuditLogRepository auditLogRepository;
    private final PasswordEncoder passwordEncoder;
    private final RequestMappingHandlerMapping handlerMapping;

    public AdminController(UserRepository userRepository,
                           CompagnieRepository compagnieRepository,
                           AuthorityRepository authorityRepository,
                           AuthorityDefinitionRepository authorityDefinitionRepository,
                           AuditLogRepository auditLogRepository,
                           PasswordEncoder passwordEncoder,
                           @Qualifier("requestMappingHandlerMapping") RequestMappingHandlerMapping handlerMapping) {
        this.userRepository                 = userRepository;
        this.compagnieRepository            = compagnieRepository;
        this.authorityRepository            = authorityRepository;
        this.authorityDefinitionRepository  = authorityDefinitionRepository;
        this.auditLogRepository             = auditLogRepository;
        this.passwordEncoder                = passwordEncoder;
        this.handlerMapping                 = handlerMapping;
    }

    public record RouteInfo(String name, String description, String url, String controllerName, String methodName, String httpMethod) {}

    public record CommandeInfo(String nom, String description, String type,
                                String planification, String jourExecution,
                                String destinataire, String typesSurveilles, String statut) {}

    @Value("${app.rappel.interval-ms:1800000}")
    private long rappelIntervalMs;

    @Value("${app.mail.to:}")
    private String mailToValidation;

    @Value("${app.mail.to.remise:}")
    private String mailToRemise;

    private static String formatName(String camelCase) {
        String spaced = camelCase.replaceAll("([a-z])([A-Z])", "$1 $2");
        return Character.toUpperCase(spaced.charAt(0)) + spaced.substring(1);
    }

    private static String buildRouteDescription(String httpMethod, String javaMethod, String pattern) {
        String action = formatName(javaMethod);
        if (javaMethod.startsWith("get") || javaMethod.startsWith("show") || javaMethod.startsWith("view") || javaMethod.startsWith("index")) {
            return "Affiche ou consulte la ressource exposee sur " + pattern + ".";
        }
        if (javaMethod.startsWith("create") || javaMethod.startsWith("add") || javaMethod.startsWith("save")) {
            return "Permet de creer une ressource via " + pattern + ".";
        }
        if (javaMethod.startsWith("update") || javaMethod.startsWith("edit") || javaMethod.startsWith("modifier")) {
            return "Permet de mettre a jour une ressource via " + pattern + ".";
        }
        if (javaMethod.startsWith("delete") || javaMethod.startsWith("remove") || javaMethod.startsWith("supprimer")) {
            return "Permet de supprimer une ressource via " + pattern + ".";
        }
        return "Endpoint " + httpMethod + " utilise pour " + action.toLowerCase() + " sur " + pattern + ".";
    }

    private List<RouteInfo> buildRoutes() {
        return handlerMapping.getHandlerMethods().entrySet().stream()
            .flatMap(entry -> {
                var info   = entry.getKey();
                HandlerMethod method = entry.getValue();
                var patterns    = info.getPatternValues();
                var httpMethods = info.getMethodsCondition().getMethods();
                String controller  = method.getBeanType().getSimpleName().replace("Controller", "");
                String javaMethod  = method.getMethod().getName();
                String httpStr     = httpMethods.isEmpty() ? "GET"
                    : httpMethods.stream().map(Enum::name).sorted().collect(Collectors.joining(", "));
                String name = formatName(javaMethod);
                return patterns.stream().map(p -> new RouteInfo(
                        name,
                        buildRouteDescription(httpStr, javaMethod, p),
                        p,
                        controller,
                        javaMethod,
                        httpStr));
            })
            .sorted(Comparator.comparing(RouteInfo::url).thenComparing(RouteInfo::httpMethod))
            .collect(Collectors.toList());
    }

    // ===== INDEX =====

    @GetMapping
    public String index(@RequestParam(defaultValue = "users") String tab,
                        @RequestParam(required = false) String auditSearch,
                        @RequestParam(defaultValue = "0") int auditPage,
                        Model model, Authentication auth) {
        model.addAttribute("loggedUser", userRepository.findByEmail(auth.getName()).orElseThrow());
        model.addAttribute("users",      userRepository.findAllByOrderByCreatedAtDesc());
        model.addAttribute("compagnies", compagnieRepository.findAll(Sort.by("name")));
        model.addAttribute("authorities", authorityRepository.findAllWithUser());
        model.addAttribute("authorityDefinitions", authorityDefinitionRepository.findAll(Sort.by("name")));
        model.addAttribute("tab", tab);
        model.addAttribute("routes", buildRoutes());

        long intervalMin = rappelIntervalMs / 60000;
        String planification = "Toutes les " + intervalMin + " minute" + (intervalMin > 1 ? "s" : "");
        model.addAttribute("commandes", List.of(
            new CommandeInfo(
                "Rappel Validations",
                "Envoie des e-mails de rappel pour les demandes de validation toujours en attente",
                "Scheduled",
                planification,
                "Lundi – Vendredi",
                mailToValidation,
                "FACTURATION / EN_ATTENTE",
                "Actif"),
            new CommandeInfo(
                "Rappel Remises",
                "Envoie des e-mails de rappel pour les demandes de remise toujours en attente",
                "Scheduled",
                planification,
                "Lundi – Vendredi",
                mailToRemise,
                "REMISE / EN_ATTENTE, EN_ATTENTE_DIRECTION",
                "Actif")
        ));

        Page<com.dtapp.entity.AuditLog> auditPageData = auditLogRepository.search(
                auditSearch,
                PageRequest.of(0, MAX_TABLE_ROWS, Sort.by(Sort.Direction.DESC, "createdAt"))
        );
        model.addAttribute("auditLogs",        auditPageData.getContent());
        model.addAttribute("auditSearch",      auditSearch != null ? auditSearch : "");
        model.addAttribute("auditCurrentPage", 0);
        model.addAttribute("auditTotalPages",  0);
        model.addAttribute("auditTotalItems",  auditPageData.getTotalElements());

        return "admin/index";
    }

    // ===== CONVERSION XLSX / CSV =====

    @PostMapping("/convert")
    @ResponseBody
    public ResponseEntity<byte[]> convert(@RequestParam MultipartFile file,
                                          @RequestParam String direction) {
        if (file.isEmpty()) {
            return ResponseEntity.badRequest()
                    .body("Aucun fichier sélectionné.".getBytes(StandardCharsets.UTF_8));
        }
        try {
            byte[] result;
            String outputName;
            String contentType;
            String baseName = baseName(file.getOriginalFilename());

            if ("xlsx-to-csv".equals(direction)) {
                result      = xlsxToCsv(file.getInputStream());
                outputName  = baseName + ".csv";
                contentType = "text/csv";
            } else if ("csv-to-xlsx".equals(direction)) {
                result      = csvToXlsx(file.getInputStream());
                outputName  = baseName + ".xlsx";
                contentType = "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";
            } else {
                return ResponseEntity.badRequest()
                        .body("Direction de conversion invalide.".getBytes(StandardCharsets.UTF_8));
            }

            return ResponseEntity.ok()
                    .header(HttpHeaders.CONTENT_DISPOSITION, "attachment; filename=\"" + outputName + "\"")
                    .contentType(MediaType.parseMediaType(contentType))
                    .body(result);
        } catch (Exception e) {
            log.error("Erreur de conversion fichier", e);
            return ResponseEntity.badRequest()
                    .body(("Erreur lors de la conversion : " + e.getMessage()).getBytes(StandardCharsets.UTF_8));
        }
    }

    private String baseName(String filename) {
        if (filename == null) return "converted";
        int dot = filename.lastIndexOf('.');
        return dot > 0 ? filename.substring(0, dot) : filename;
    }

    private byte[] xlsxToCsv(InputStream is) throws IOException {
        StringBuilder sb = new StringBuilder();
        try (Workbook workbook = new XSSFWorkbook(is)) {
            Sheet sheet = workbook.getSheetAt(0);
            DataFormatter fmt = new DataFormatter();
            for (Row row : sheet) {
                int last = row.getLastCellNum();
                for (int i = 0; i < last; i++) {
                    Cell cell = row.getCell(i, Row.MissingCellPolicy.CREATE_NULL_AS_BLANK);
                    String val = fmt.formatCellValue(cell);
                    if (i > 0) sb.append(',');
                    if (val.contains(",") || val.contains("\"") || val.contains("\n") || val.contains("\r")) {
                        sb.append('"').append(val.replace("\"", "\"\"")).append('"');
                    } else {
                        sb.append(val);
                    }
                }
                sb.append("\r\n");
            }
        }
        return sb.toString().getBytes(StandardCharsets.UTF_8);
    }

    private byte[] csvToXlsx(InputStream is) throws IOException {
        try (BufferedReader reader = new BufferedReader(new InputStreamReader(is, StandardCharsets.UTF_8));
             Workbook workbook = new XSSFWorkbook();
             ByteArrayOutputStream out = new ByteArrayOutputStream()) {
            Sheet sheet = workbook.createSheet("Data");
            String line;
            int rowNum = 0;
            while ((line = reader.readLine()) != null) {
                Row row = sheet.createRow(rowNum++);
                String[] cells = parseCsvLine(line);
                for (int i = 0; i < cells.length; i++) {
                    row.createCell(i).setCellValue(cells[i]);
                }
            }
            workbook.write(out);
            return out.toByteArray();
        }
    }

    private String[] parseCsvLine(String line) {
        List<String> cells = new ArrayList<>();
        boolean inQuotes = false;
        StringBuilder current = new StringBuilder();
        for (int i = 0; i < line.length(); i++) {
            char c = line.charAt(i);
            if (inQuotes) {
                if (c == '"') {
                    if (i + 1 < line.length() && line.charAt(i + 1) == '"') {
                        current.append('"');
                        i++;
                    } else {
                        inQuotes = false;
                    }
                } else {
                    current.append(c);
                }
            } else {
                if (c == '"') {
                    inQuotes = true;
                } else if (c == ',') {
                    cells.add(current.toString());
                    current = new StringBuilder();
                } else {
                    current.append(c);
                }
            }
        }
        cells.add(current.toString());
        return cells.toArray(new String[0]);
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
    public String updateCompagnie(@PathVariable int id,
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
    public String deleteCompagnie(@PathVariable int id, RedirectAttributes ra) {
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
    public String updateUser(@PathVariable int id,
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
    public String deleteUser(@PathVariable int id, Authentication auth, RedirectAttributes ra) {
        var current = userRepository.findByEmail(auth.getName()).orElseThrow();
        if (current.getId().equals(id)) {
            ra.addFlashAttribute("error", "Vous ne pouvez pas supprimer votre propre compte.");
            return "redirect:/admin?tab=users";
        }
        userRepository.deleteById(id);
        ra.addFlashAttribute("success", "Utilisateur supprimé.");
        return "redirect:/admin?tab=users";
    }

    // ===== AUTHORITY DEFINITIONS =====

    @PostMapping("/authority-definitions/create")
    public String createAuthorityDefinition(@RequestParam String name, RedirectAttributes ra) {
        String trimmed = name.trim().toUpperCase();
        if (trimmed.isEmpty()) {
            ra.addFlashAttribute("error", "Le nom de l'authority est requis.");
            return "redirect:/admin?tab=authorities";
        }
        if (authorityDefinitionRepository.existsByName(trimmed)) {
            ra.addFlashAttribute("error", "Cette authority existe déjà.");
            return "redirect:/admin?tab=authorities";
        }
        AuthorityDefinition def = new AuthorityDefinition();
        def.setName(trimmed);
        authorityDefinitionRepository.save(def);
        ra.addFlashAttribute("success", "Authority \"" + trimmed + "\" créée.");
        return "redirect:/admin?tab=authorities";
    }

    @PostMapping("/authority-definitions/{id}/delete")
    public String deleteAuthorityDefinition(@PathVariable int id, RedirectAttributes ra) {
        authorityDefinitionRepository.deleteById(id);
        ra.addFlashAttribute("success", "Authority supprimée.");
        return "redirect:/admin?tab=authorities";
    }

    // ===== AUTHORITIES =====

    @PostMapping("/authorities/create")
    public String createAuthority(@RequestParam int userId,
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
    public String deleteAuthority(@PathVariable int id, RedirectAttributes ra) {
        authorityRepository.deleteById(id);
        ra.addFlashAttribute("success", "Rôle retiré.");
        return "redirect:/admin?tab=authorities";
    }
}
