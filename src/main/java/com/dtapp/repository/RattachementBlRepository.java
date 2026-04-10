package com.dtapp.repository;

import com.dtapp.entity.RattachementBl;
import org.springframework.data.domain.Page;
import org.springframework.data.domain.Pageable;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;
import org.springframework.stereotype.Repository;

import java.time.LocalDateTime;
import java.util.List;

@Repository
public interface RattachementBlRepository extends JpaRepository<RattachementBl, Long> {

    List<RattachementBl> findByTypeOrderByCreatedAtDesc(String type);

    List<RattachementBl> findByTypeAndStatutOrderByCreatedAtAsc(String type, String statut);

    @Query("SELECT r FROM RattachementBl r WHERE r.type = :type ORDER BY r.createdAt DESC")
    Page<RattachementBl> findByTypePaged(@Param("type") String type, Pageable pageable);

    @Query("SELECT r FROM RattachementBl r WHERE r.type = :type " +
           "AND (:dateStart IS NULL OR r.createdAt >= :dateStart) " +
           "AND (:dateEnd   IS NULL OR r.createdAt <  :dateEnd) " +
           "AND ('' = :nom    OR LOWER(r.nom)    LIKE LOWER(CONCAT('%', :nom,    '%'))) " +
           "AND ('' = :prenom OR LOWER(r.prenom) LIKE LOWER(CONCAT('%', :prenom, '%'))) " +
           "AND ('' = :email  OR LOWER(r.email)  LIKE LOWER(CONCAT('%', :email,  '%'))) " +
           "AND ('' = :bl     OR LOWER(r.bl)     LIKE LOWER(CONCAT('%', :bl,     '%'))) " +
           "AND ('' = :maison OR LOWER(r.maison) LIKE LOWER(CONCAT('%', :maison, '%'))) " +
           "AND ('' = :statut OR r.statut = :statut) " +
           "ORDER BY r.createdAt DESC")
    Page<RattachementBl> findByTypeWithFilters(@Param("type")      String type,
                                               @Param("dateStart") LocalDateTime dateStart,
                                               @Param("dateEnd")   LocalDateTime dateEnd,
                                               @Param("nom")       String nom,
                                               @Param("prenom")    String prenom,
                                               @Param("email")     String email,
                                               @Param("bl")        String bl,
                                               @Param("maison")    String maison,
                                               @Param("statut")    String statut,
                                               Pageable pageable);
}
