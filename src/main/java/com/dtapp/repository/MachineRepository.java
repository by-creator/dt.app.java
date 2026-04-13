package com.dtapp.repository;

import com.dtapp.entity.Machine;
import org.springframework.data.domain.Page;
import org.springframework.data.domain.Pageable;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;

public interface MachineRepository extends JpaRepository<Machine, Long> {

    @Query("""
        SELECT m FROM Machine m
        WHERE :search IS NULL OR :search = ''
           OR LOWER(m.name)       LIKE LOWER(CONCAT('%', :search, '%'))
           OR LOWER(m.username)   LIKE LOWER(CONCAT('%', :search, '%'))
           OR LOWER(m.serviceTag) LIKE LOWER(CONCAT('%', :search, '%'))
           OR LOWER(m.model)      LIKE LOWER(CONCAT('%', :search, '%'))
           OR LOWER(m.service)    LIKE LOWER(CONCAT('%', :search, '%'))
           OR LOWER(m.sites)      LIKE LOWER(CONCAT('%', :search, '%'))
        ORDER BY m.createdAt DESC
        """)
    Page<Machine> searchPaged(@Param("search") String search, Pageable pageable);

    @Query("""
        SELECT m FROM Machine m
        WHERE (:nom      IS NULL OR :nom      = '' OR LOWER(m.name)     LIKE LOWER(CONCAT('%', :nom,      '%')))
          AND (:ajow     IS NULL OR :ajow     = '' OR LOWER(m.ajowName) LIKE LOWER(CONCAT('%', :ajow,     '%')))
          AND (:type     IS NULL OR :type     = '' OR LOWER(m.type)     LIKE LOWER(CONCAT('%', :type,     '%')))
          AND (:user     IS NULL OR :user     = '' OR LOWER(m.username) LIKE LOWER(CONCAT('%', :user,     '%')))
          AND (:model    IS NULL OR :model    = '' OR LOWER(m.model)    LIKE LOWER(CONCAT('%', :model,    '%')))
          AND (:service  IS NULL OR :service  = '' OR LOWER(m.service)  LIKE LOWER(CONCAT('%', :service,  '%')))
          AND (:site     IS NULL OR :site     = '' OR LOWER(m.sites)    LIKE LOWER(CONCAT('%', :site,     '%')))
        ORDER BY m.createdAt DESC
        """)
    Page<Machine> filterByColumns(@Param("nom") String nom,
                                  @Param("ajow") String ajow,
                                  @Param("type") String type,
                                  @Param("user") String user,
                                  @Param("model") String model,
                                  @Param("service") String service,
                                  @Param("site") String site,
                                  Pageable pageable);
}