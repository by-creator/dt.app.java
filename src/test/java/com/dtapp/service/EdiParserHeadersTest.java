package com.dtapp.service;

import org.junit.jupiter.api.Test;

import java.util.List;
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

    @Test
    void getHeadersMatchesReferenceXlsOrder() {
        EdiParser parser = new EdiParser();

        List<String> labels = List.copyOf(parser.getHeaders().values());

        assertEquals(List.of(
                "BL Number",
                "ImportExport",
                "Stevedore",
                "Shipping Agent",
                "Estimated Departure Date",
                "Call Number",
                "Shipper",
                "Forwarder",
                "Related Customer",
                "Forwarding Agent",
                "Final Destination Country",
                "Manifest",
                "Number of Yard Items",
                "Number of Packages",
                "SlotFile",
                "TransportMode",
                "Consignee",
                "CustomReleaseOrder",
                "CustomReleaseOrderDate",
                "DeliveryOrder",
                "DeliveryOrderDate",
                "MasterBL",
                "BLVolume",
                "BLWeight",
                "Incoterm",
                "Port_of_Loading UNLOCODE",
                "Reception_Location UNLOCODE",
                "Transshipment port 1 UNLOCODE",
                "Transshipment port 2 UNLOCODE",
                "Commodity",
                "YardItemType",
                "UnitOfMeasure",
                "Comment",
                "DirectionCode",
                "Agent Name",
                "BLItem YardItemType",
                "BLItem Comment",
                "BLItem YardItemNumber",
                "BLItem AllowInvalidYardItemNumber",
                "BLItem YardItemCode",
                "BLItem OutOfGauge",
                "BLItem Commodity",
                "BLItem HS Code",
                "BLItem YardItemUnloadingDate",
                "BLItem Commodity Volume",
                "BLItem Commodity Weight",
                "BLItem Commodity Packages",
                "BLItem ImportExport",
                "BLItem CustomNumber",
                "BLItem SealNumber1",
                "BLItem SealNumber2",
                "BLItem Commodity HazardousClass",
                "BLItem BarCode",
                "BLItem VehicleModel",
                "BLItem ChassisNumber",
                "BLItem GrossWeight",
                "OutGoingCallNumber",
                "OutGoingSlotFile",
                "Is Lifter",
                "Stacked Vehicle Chassis Number",
                "Stacked Vehicle Model",
                "Stacked Vehicle Weight",
                "Stacked Vehicle Volume",
                "New Transshipment BL",
                "Shipper Name",
                "Freight Prepaid / Collect",
                "Shipping Line Export BL Number",
                "Is Transfer",
                "BLItem HazardousClass",
                "Attach to BL",
                "Adresse 2",
                "Adresse 3",
                "Adresse 4",
                "Adresse 5",
                "Notify1",
                "Notify2",
                "Notify3",
                "Notify4",
                "Notify5"
        ), labels);
    }
}
