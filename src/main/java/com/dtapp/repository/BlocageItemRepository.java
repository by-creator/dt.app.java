package com.dtapp.repository;

import com.dtapp.entity.BlocageItem;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;
import org.springframework.stereotype.Repository;

import java.util.List;
import java.util.Optional;

@Repository
public interface BlocageItemRepository extends JpaRepository<BlocageItem, Integer> {

    Optional<BlocageItem> findTopByItemOrderByCreatedAtDesc(String item);

    @Query("SELECT b FROM BlocageItem b WHERE " +
           "(:search IS NULL OR :search = '' OR LOWER(b.item) LIKE LOWER(CONCAT('%', :search, '%'))) " +
           "ORDER BY b.createdAt DESC")
    List<BlocageItem> searchAll(@Param("search") String search);
}
