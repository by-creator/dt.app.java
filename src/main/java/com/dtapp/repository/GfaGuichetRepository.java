package com.dtapp.repository;

import com.dtapp.entity.GfaGuichet;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;

import java.util.List;

@Repository
public interface GfaGuichetRepository extends JpaRepository<GfaGuichet, Long> {

    List<GfaGuichet> findAllByOrderByNumeroAsc();

    List<GfaGuichet> findAllByActifTrueOrderByNumeroAsc();

    long countByActifTrue();
}
