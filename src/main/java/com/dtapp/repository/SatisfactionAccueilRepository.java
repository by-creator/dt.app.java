package com.dtapp.repository;

import com.dtapp.entity.SatisfactionAccueil;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;

@Repository
public interface SatisfactionAccueilRepository extends JpaRepository<SatisfactionAccueil, Long> {
}