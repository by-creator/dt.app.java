package com.dtapp.repository;

import com.dtapp.entity.AuthorityDefinition;
import org.springframework.data.jpa.repository.JpaRepository;

public interface AuthorityDefinitionRepository extends JpaRepository<AuthorityDefinition, Integer> {
    boolean existsByName(String name);
}
