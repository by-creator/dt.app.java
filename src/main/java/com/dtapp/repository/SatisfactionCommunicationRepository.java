package com.dtapp.repository;

import com.dtapp.entity.SatisfactionCommunication;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;

@Repository
public interface SatisfactionCommunicationRepository extends JpaRepository<SatisfactionCommunication, Long> {
}