package com.dtapp.repository;

import com.dtapp.entity.SatisfactionGeneral;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;

@Repository
public interface SatisfactionGeneralRepository extends JpaRepository<SatisfactionGeneral, Long> {
}