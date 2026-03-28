package com.dtapp.repository;

import com.dtapp.entity.RattachementBl;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;

import java.util.List;

@Repository
public interface RattachementBlRepository extends JpaRepository<RattachementBl, Long> {

    List<RattachementBl> findByTypeOrderByCreatedAtDesc(String type);
}
