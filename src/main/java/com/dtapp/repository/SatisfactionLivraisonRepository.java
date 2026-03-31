package com.dtapp.repository;

import com.dtapp.entity.SatisfactionLivraison;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;

@Repository
public interface SatisfactionLivraisonRepository extends JpaRepository<SatisfactionLivraison, Long> {
}