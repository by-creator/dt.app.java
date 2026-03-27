package com.dtapp.repository;

import com.dtapp.entity.Compagnie;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;

import java.util.Optional;

@Repository
public interface CompagnieRepository extends JpaRepository<Compagnie, Integer> {

    Optional<Compagnie> findByName(String name);
}
