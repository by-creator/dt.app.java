package com.dtapp.repository;

import com.dtapp.entity.RapportSuiviVides;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;
import org.springframework.stereotype.Repository;

import java.util.List;

@Repository
public interface RapportSuiviVidesRepository extends JpaRepository<RapportSuiviVides, Long> {

    @Query("SELECT DISTINCT r.shipowner FROM RapportSuiviVides r WHERE r.shipowner IS NOT NULL AND r.shipowner <> '' ORDER BY r.shipowner")
    List<String> findDistinctShipowners();

    @Query("SELECT DISTINCT r.itemType FROM RapportSuiviVides r WHERE r.itemType IS NOT NULL AND r.itemType <> '' ORDER BY r.itemType")
    List<String> findDistinctItemTypes();

    @Query("SELECT DISTINCT r.equipmentTypeSize FROM RapportSuiviVides r WHERE r.equipmentTypeSize IS NOT NULL AND r.equipmentTypeSize <> '' ORDER BY r.equipmentTypeSize")
    List<String> findDistinctEquipmentTypeSizes();

    @Query("SELECT DISTINCT r.eventCode FROM RapportSuiviVides r WHERE r.eventCode IS NOT NULL AND r.eventCode <> '' ORDER BY r.eventCode")
    List<String> findDistinctEventCodes();

    @Query("SELECT DISTINCT r.eventFamily FROM RapportSuiviVides r WHERE r.eventFamily IS NOT NULL AND r.eventFamily <> '' ORDER BY r.eventFamily")
    List<String> findDistinctEventFamilies();

    @Query("SELECT r FROM RapportSuiviVides r WHERE " +
           "(:shipowner IS NULL OR r.shipowner = :shipowner) AND " +
           "(:itemType IS NULL OR r.itemType = :itemType) AND " +
           "(:equipmentNumber IS NULL OR LOWER(r.equipmentNumber) LIKE LOWER(CONCAT('%', :equipmentNumber, '%'))) AND " +
           "(:equipmentTypeSize IS NULL OR r.equipmentTypeSize = :equipmentTypeSize) AND " +
           "(:eventCode IS NULL OR r.eventCode = :eventCode) AND " +
           "(:eventFamily IS NULL OR r.eventFamily = :eventFamily) AND " +
           "(:dateFrom IS NULL OR r.eventDate >= :dateFrom) AND " +
           "(:dateTo IS NULL OR r.eventDate <= :dateTo) " +
           "ORDER BY r.createdAt DESC")
    List<RapportSuiviVides> findFiltered(
            @Param("shipowner")         String shipowner,
            @Param("itemType")          String itemType,
            @Param("equipmentNumber")   String equipmentNumber,
            @Param("equipmentTypeSize") String equipmentTypeSize,
            @Param("eventCode")         String eventCode,
            @Param("eventFamily")       String eventFamily,
            @Param("dateFrom")          String dateFrom,
            @Param("dateTo")            String dateTo
    );
}
