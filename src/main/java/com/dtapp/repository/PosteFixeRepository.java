package com.dtapp.repository;

import com.dtapp.entity.PosteFixe;
import org.springframework.data.domain.Page;
import org.springframework.data.domain.Pageable;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;

public interface PosteFixeRepository extends JpaRepository<PosteFixe, Long> {

    @Query("""
        SELECT p FROM PosteFixe p
        WHERE :search IS NULL OR :search = ''
           OR LOWER(p.nom)      LIKE LOWER(CONCAT('%', :search, '%'))
           OR LOWER(p.prenom)   LIKE LOWER(CONCAT('%', :search, '%'))
           OR LOWER(p.annuaire) LIKE LOWER(CONCAT('%', :search, '%'))
           OR LOWER(p.type)     LIKE LOWER(CONCAT('%', :search, '%'))
        ORDER BY p.createdAt DESC
        """)
    Page<PosteFixe> searchPaged(@Param("search") String search, Pageable pageable);
}