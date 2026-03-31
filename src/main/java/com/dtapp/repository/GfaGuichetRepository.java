package com.dtapp.repository;

import com.dtapp.entity.GfaGuichet;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;

import java.util.List;
import java.util.Optional;

@Repository
public interface GfaGuichetRepository extends JpaRepository<GfaGuichet, Long> {

    List<GfaGuichet> findAllByOrderByNumeroAsc();

    List<GfaGuichet> findAllByActifTrueOrderByNumeroAsc();

    long countByActifTrue();

    boolean existsByNumero(String numero);

    Optional<GfaGuichet> findByNumero(String numero);
}
