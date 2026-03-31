package com.dtapp.repository;

import com.dtapp.entity.SatisfactionFacturation;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;

@Repository
public interface SatisfactionFacturationRepository extends JpaRepository<SatisfactionFacturation, Long> {
}