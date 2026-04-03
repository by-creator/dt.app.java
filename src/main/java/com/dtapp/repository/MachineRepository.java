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
}