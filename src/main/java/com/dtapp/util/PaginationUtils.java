package com.dtapp.util;

import org.springframework.data.domain.Page;
import org.springframework.data.domain.PageImpl;
import org.springframework.data.domain.PageRequest;
import org.springframework.data.domain.Pageable;
import org.springframework.data.domain.Sort;
import org.springframework.ui.Model;

import java.util.Collections;
import java.util.List;

public final class PaginationUtils {

    public static final int DEFAULT_PAGE_SIZE = 25;
    public static final int MAX_PAGE_SIZE = 100;

    private PaginationUtils() {
    }

    public static Pageable pageable(int page, Integer size) {
        return pageable(page, size, Sort.unsorted());
    }

    public static Pageable pageable(int page, Integer size, Sort sort) {
        return PageRequest.of(normalizePage(page), normalizeSize(size), sort == null ? Sort.unsorted() : sort);
    }

    public static <T> Page<T> fromList(List<T> items, int page, Integer size) {
        List<T> safeItems = items == null ? Collections.emptyList() : items;
        int normalizedPage = normalizePage(page);
        int normalizedSize = normalizeSize(size);
        int start = Math.min(normalizedPage * normalizedSize, safeItems.size());
        int end = Math.min(start + normalizedSize, safeItems.size());
        return new PageImpl<>(safeItems.subList(start, end), PageRequest.of(normalizedPage, normalizedSize), safeItems.size());
    }

    public static void addPageAttributes(Model model, Page<?> pageData) {
        addPageAttributes(model, pageData, null);
    }

    public static void addPageAttributes(Model model, Page<?> pageData, String prefix) {
        String safePrefix = prefix == null || prefix.isBlank() ? "" : prefix;
        model.addAttribute("currentPage", pageData.getNumber());
        model.addAttribute("totalPages", pageData.getTotalPages());
        model.addAttribute("totalItems", pageData.getTotalElements());
        model.addAttribute("pageSize", pageData.getSize());
        model.addAttribute("pageStart", pageData.getTotalElements() == 0 ? 0 : (long) pageData.getNumber() * pageData.getSize() + 1);
        model.addAttribute("pageEnd", pageData.getTotalElements() == 0 ? 0 : (long) pageData.getNumber() * pageData.getSize() + pageData.getNumberOfElements());
        if (!safePrefix.isEmpty()) {
            model.addAttribute(safePrefix + "CurrentPage", pageData.getNumber());
            model.addAttribute(safePrefix + "TotalPages", pageData.getTotalPages());
            model.addAttribute(safePrefix + "TotalItems", pageData.getTotalElements());
            model.addAttribute(safePrefix + "PageSize", pageData.getSize());
            model.addAttribute(safePrefix + "PageStart", pageData.getTotalElements() == 0 ? 0 : (long) pageData.getNumber() * pageData.getSize() + 1);
            model.addAttribute(safePrefix + "PageEnd", pageData.getTotalElements() == 0 ? 0 : (long) pageData.getNumber() * pageData.getSize() + pageData.getNumberOfElements());
        }
    }

    private static int normalizePage(int page) {
        return Math.max(page, 0);
    }

    private static int normalizeSize(Integer size) {
        if (size == null || size <= 0) {
            return DEFAULT_PAGE_SIZE;
        }
        return Math.min(size, MAX_PAGE_SIZE);
    }
}
