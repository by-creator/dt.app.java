package com.dtapp.repository;

import com.dtapp.entity.GfaService;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;

import java.util.List;

@Repository
public interface GfaServiceRepository extends JpaRepository<GfaService, Long> {

    List<GfaService> findAllByOrderByNomAsc();

    List<GfaService> findAllByActifTrueOrderByIdAsc();

    boolean existsByCodeIgnoreCase(String code);

    boolean existsByCodeIgnoreCaseAndIdNot(String code, Long id);

    long countByActifTrue();
}
