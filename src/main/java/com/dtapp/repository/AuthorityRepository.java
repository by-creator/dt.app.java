package com.dtapp.repository;

import com.dtapp.entity.Authority;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.stereotype.Repository;

@Repository
public interface AuthorityRepository extends JpaRepository<Authority, Integer> {

    @Query("SELECT COUNT(DISTINCT a.authority) FROM Authority a")
    long countDistinctAuthorities();

    long countByAuthority(String authority);
}
