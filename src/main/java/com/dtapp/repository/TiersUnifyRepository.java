package com.dtapp.repository;

import com.dtapp.entity.TiersUnify;
import jakarta.transaction.Transactional;
import org.springframework.data.domain.Page;
import org.springframework.data.domain.Pageable;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Modifying;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;

public interface TiersUnifyRepository extends JpaRepository<TiersUnify, Long> {

    @Query("SELECT t FROM TiersUnify t WHERE :search IS NULL OR :search = '' OR " +
           "LOWER(t.raisonSociale) LIKE LOWER(CONCAT('%', :search, '%')) OR " +
           "LOWER(t.compteIpaki) LIKE LOWER(CONCAT('%', :search, '%')) OR " +
           "LOWER(t.compteNeptune) LIKE LOWER(CONCAT('%', :search, '%'))")
    Page<TiersUnify> search(@Param("search") String search, Pageable pageable);

    @Modifying
    @Transactional
    @Query(value = "DELETE FROM tiers_unify WHERE " +
                   "compte_ipaki IS NULL OR compte_ipaki = '' OR " +
                   "(compte_ipaki NOT LIKE 'SN00%' AND compte_ipaki NOT LIKE 'ND%' AND compte_ipaki NOT REGEXP '^[0-9]')",
           nativeQuery = true)
    int deleteInvalidCompteIpaki();
}
