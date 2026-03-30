package com.dtapp.repository;

import com.dtapp.entity.User;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Modifying;
import org.springframework.data.jpa.repository.Query;
import org.springframework.stereotype.Repository;
import org.springframework.transaction.annotation.Transactional;

import java.time.LocalDate;
import java.time.LocalDateTime;
import java.util.List;
import java.util.Optional;

@Repository
public interface UserRepository extends JpaRepository<User, Integer> {

    Optional<User> findByEmail(String email);

    long countByEnabledTrue();

    List<User> findTop5ByOrderByCreatedAtDesc();

    long countByCreatedAtBetween(LocalDateTime start, LocalDateTime end);

    default long countCreatedToday() {
        LocalDate today = LocalDate.now();
        return countByCreatedAtBetween(today.atStartOfDay(), today.plusDays(1).atStartOfDay());
    }

    List<User> findAllByOrderByCreatedAtDesc();

    @Modifying
    @Transactional
    @Query("UPDATE User u SET u.compagnie = null WHERE u.compagnie.id = :compagnieId")
    void unlinkFromCompagnie(@org.springframework.data.repository.query.Param("compagnieId") Integer compagnieId);
}
