package com.dtapp.service;

import lombok.AllArgsConstructor;
import lombok.Data;

@Data
@AllArgsConstructor
public class AutomationDocumentLink {
    private String fileName;
    private String viewUrl;
    private String downloadUrl;
    private String key;
}
