package com.dtapp.repository;

import com.dtapp.entity.UpdateIesAccount;
import org.springframework.data.domain.Sort;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;
import java.util.Optional;

public interface UpdateIesAccountRepository extends JpaRepository<UpdateIesAccount, Long> {

    Optional<UpdateIesAccount> findTopByCompteOrderByCreatedAtDesc(String compte);

    default List<UpdateIesAccount> findAllForAdmin() {
        return findAll(Sort.by(Sort.Direction.DESC, "createdAt"));
    }
}
