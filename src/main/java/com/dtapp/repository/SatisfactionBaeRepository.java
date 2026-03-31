package com.dtapp.repository;

import com.dtapp.entity.SatisfactionBae;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;

@Repository
public interface SatisfactionBaeRepository extends JpaRepository<SatisfactionBae, Long> {
}