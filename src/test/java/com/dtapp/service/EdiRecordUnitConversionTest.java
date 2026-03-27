package com.dtapp.service;

import org.junit.jupiter.api.Test;

import java.nio.charset.Charset;

import static org.junit.jupiter.api.Assertions.assertEquals;

class EdiRecordUnitConversionTest {

    @Test
    void fromLineMatchesLaravelWeightAndVolumeUnits() {
        byte[] raw = new byte[1400];
        java.util.Arrays.fill(raw, (byte) ' ');

        write(raw, 61, "R");
        write(raw, 62, "RS329162730");
        write(raw, 281, "000007775000");
        write(raw, 1296, "000008000000");
        write(raw, 1308, "000000061800");

        EdiRecord record = EdiRecord.fromLine(raw, Charset.forName("Windows-1252"));

        assertEquals(7.775d, parseLocalizedDouble(record.data.get("bl_weight")), 0.0001d);
        assertEquals(7.775d, parseLocalizedDouble(record.data.get("blitem_commodity_weight")), 0.0001d);
        assertEquals(61.8d, parseLocalizedDouble(record.data.get("bl_volume")), 0.0001d);
        assertEquals(61.8d, parseLocalizedDouble(record.data.get("blitem_commodity_volume")), 0.0001d);
        assertEquals("VEH 6001-9000Kgs", record.data.get("blitem_commodity"));
    }

    private static void write(byte[] raw, int offset, String value) {
        byte[] bytes = value.getBytes(Charset.forName("Windows-1252"));
        System.arraycopy(bytes, 0, raw, offset, bytes.length);
    }

    private static double parseLocalizedDouble(String value) {
        return Double.parseDouble(value.replace(',', '.'));
    }
}
