package com.dtapp.repository;

import com.dtapp.entity.Codification;
import com.dtapp.entity.Compagnie;
import org.springframework.data.domain.Page;
import org.springframework.data.domain.Pageable;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;
import org.springframework.stereotype.Repository;

import java.util.Optional;

@Repository
public interface CodificationRepository extends JpaRepository<Codification, Integer> {

    @Query("SELECT c FROM Codification c WHERE c.compagnie = :compagnie ORDER BY c.createdAt DESC")
    Page<Codification> findByCompagnie(@Param("compagnie") Compagnie compagnie, Pageable pageable);

    @Query("SELECT c FROM Codification c WHERE c.compagnie = :compagnie AND c.callNumber LIKE %:search% ORDER BY c.createdAt DESC")
    Page<Codification> findByCompagnieAndCallNumberContaining(@Param("compagnie") Compagnie compagnie, @Param("search") String search, Pageable pageable);

    Optional<Codification> findByCallNumber(String callNumber);
}
