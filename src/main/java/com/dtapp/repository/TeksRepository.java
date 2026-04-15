package com.dtapp.repository;

import com.dtapp.entity.Teks;
import org.springframework.data.domain.Page;
import org.springframework.data.domain.Pageable;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;
import org.springframework.stereotype.Repository;

@Repository
public interface TeksRepository extends JpaRepository<Teks, Long> {

    @Query("""
            SELECT t FROM Teks t
            WHERE (:search = '' OR LOWER(t.bl) LIKE LOWER(CONCAT('%', :search, '%'))
                   OR LOWER(t.chassis) LIKE LOWER(CONCAT('%', :search, '%'))
                   OR LOWER(t.fileName) LIKE LOWER(CONCAT('%', :search, '%')))
              AND (:filterBl = '' OR LOWER(t.bl) LIKE LOWER(CONCAT('%', :filterBl, '%')))
              AND (:filterChassis = '' OR LOWER(t.chassis) LIKE LOWER(CONCAT('%', :filterChassis, '%')))
            ORDER BY t.createdAt DESC
            """)
    Page<Teks> search(@Param("search") String search,
                      @Param("filterBl") String filterBl,
                      @Param("filterChassis") String filterChassis,
                      Pageable pageable);
}
