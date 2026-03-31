package com.dtapp.repository;

import com.dtapp.entity.SatisfactionInfo;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;

@Repository
public interface SatisfactionInfoRepository extends JpaRepository<SatisfactionInfo, Long> {
}