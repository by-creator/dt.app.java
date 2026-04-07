package com.dtapp.repository;

import com.dtapp.entity.RattachementBl;
import org.springframework.data.domain.Page;
import org.springframework.data.domain.Pageable;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;
import org.springframework.stereotype.Repository;

import java.util.List;

@Repository
public interface RattachementBlRepository extends JpaRepository<RattachementBl, Long> {

    List<RattachementBl> findByTypeOrderByCreatedAtDesc(String type);

    @Query("SELECT r FROM RattachementBl r WHERE r.type = :type ORDER BY r.createdAt DESC")
    Page<RattachementBl> findByTypePaged(@Param("type") String type, Pageable pageable);
}
