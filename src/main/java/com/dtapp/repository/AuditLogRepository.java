package com.dtapp.repository;

import com.dtapp.entity.AuditLog;
import org.springframework.data.domain.Page;
import org.springframework.data.domain.Pageable;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;

public interface AuditLogRepository extends JpaRepository<AuditLog, Long> {

    @Query("""
            SELECT a FROM AuditLog a
            WHERE (:search IS NULL OR :search = '' OR
                   LOWER(COALESCE(a.userName, '')) LIKE LOWER(CONCAT('%', :search, '%')) OR
                   LOWER(COALESCE(a.userEmail, '')) LIKE LOWER(CONCAT('%', :search, '%')) OR
                   LOWER(COALESCE(a.userRole, '')) LIKE LOWER(CONCAT('%', :search, '%')) OR
                   LOWER(COALESCE(a.method, '')) LIKE LOWER(CONCAT('%', :search, '%')) OR
                   LOWER(COALESCE(a.url, '')) LIKE LOWER(CONCAT('%', :search, '%')) OR
                   LOWER(COALESCE(a.routeName, '')) LIKE LOWER(CONCAT('%', :search, '%')) OR
                   LOWER(COALESCE(a.controllerAction, '')) LIKE LOWER(CONCAT('%', :search, '%')) OR
                   LOWER(COALESCE(a.ipAddress, '')) LIKE LOWER(CONCAT('%', :search, '%')))
            ORDER BY a.createdAt DESC
            """)
    Page<AuditLog> search(@Param("search") String search, Pageable pageable);
}
