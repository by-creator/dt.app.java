package com.dtapp.repository;

import com.dtapp.entity.GfaAgent;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;

import java.util.List;
import java.util.Optional;

@Repository
public interface GfaAgentRepository extends JpaRepository<GfaAgent, Long> {

    List<GfaAgent> findAllByOrderByNomAscPrenomAsc();

    Optional<GfaAgent> findFirstByGuichetIdAndActifTrueOrderByNomAscPrenomAsc(Long guichetId);

    long countByActifTrue();
}
