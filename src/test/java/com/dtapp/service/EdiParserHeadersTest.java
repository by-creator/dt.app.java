package com.dtapp.service;

import org.junit.jupiter.api.Test;

import java.util.Map;

import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.junit.jupiter.api.Assertions.assertTrue;

class EdiParserHeadersTest {

    @Test
    void getHeadersIncludesAddressAndNotifyColumns() {
        EdiParser parser = new EdiParser();

        Map<String, String> headers = parser.getHeaders();

        assertEquals("Adresse 2", headers.get("adresse_2"));
        assertEquals("Adresse 3", headers.get("adresse_3"));
        assertEquals("Adresse 4", headers.get("adresse_4"));
        assertEquals("Adresse 5", headers.get("adresse_5"));
        assertEquals("Notify1", headers.get("notify1"));
        assertEquals("Notify2", headers.get("notify2"));
        assertEquals("Notify3", headers.get("notify3"));
        assertEquals("Notify4", headers.get("notify4"));
        assertEquals("Notify5", headers.get("notify5"));

        assertTrue(headers.containsKey("adresse_2"));
        assertTrue(headers.containsKey("notify5"));
    }
}
