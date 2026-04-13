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

    @Query("SELECT b FROM BlocageItem b WHERE " +
           "(:filterItem   IS NULL OR :filterItem   = '' OR LOWER(b.item)   LIKE LOWER(CONCAT('%', :filterItem, '%'))) AND " +
           "(:filterStatut IS NULL OR :filterStatut = '' OR b.statut = :filterStatut) AND " +
           "(:filterDate   IS NULL OR :filterDate   = '' OR CAST(b.createdAt AS string) LIKE CONCAT('%', :filterDate, '%')) " +
           "ORDER BY b.createdAt DESC")
    Page<BlocageItem> filterByColumns(@Param("filterItem") String filterItem,
                                      @Param("filterStatut") String filterStatut,
                                      @Param("filterDate") String filterDate,
                                      Pageable pageable);
}
