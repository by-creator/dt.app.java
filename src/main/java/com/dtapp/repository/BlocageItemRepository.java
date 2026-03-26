package com.dtapp.repository;

import com.dtapp.entity.BlocageItem;
import org.springframework.data.domain.Page;
import org.springframework.data.domain.Pageable;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;
import org.springframework.stereotype.Repository;

import java.util.Optional;

@Repository
public interface BlocageItemRepository extends JpaRepository<BlocageItem, Integer> {

    Optional<BlocageItem> findTopByItemOrderByCreatedAtDesc(String item);

    @Query("SELECT b FROM BlocageItem b WHERE " +
           "(:search IS NULL OR :search = '' OR LOWER(b.item) LIKE LOWER(CONCAT('%', :search, '%'))) " +
           "ORDER BY b.createdAt DESC")
    Page<BlocageItem> searchPaged(@Param("search") String search, Pageable pageable);
}
