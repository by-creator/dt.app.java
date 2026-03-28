package com.dtapp.repository;

import com.dtapp.entity.GfaTicket;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;
import org.springframework.stereotype.Repository;

import java.util.List;
import java.util.Optional;

@Repository
public interface GfaTicketRepository extends JpaRepository<GfaTicket, Long> {

    List<GfaTicket> findAllByOrderByCreatedAtDesc();

    long countByStatutIgnoreCase(String statut);

    long countByServiceId(Long serviceId);

    long countByServiceIdAndStatutIgnoreCase(Long serviceId, String statut);

    boolean existsByToken(String token);

    Optional<GfaTicket> findByIdAndToken(Long id, String token);

    List<GfaTicket> findByServiceIdAndStatutIgnoreCaseOrderByCreatedAtAsc(Long serviceId, String statut);

    Optional<GfaTicket> findFirstByServiceIdAndStatutIgnoreCaseOrderByCreatedAtAsc(Long serviceId, String statut);

    Optional<GfaTicket> findTopByGuichetIdAndStatutIgnoreCaseOrderByCalledAtDescIdDesc(Long guichetId, String statut);

    @Query("SELECT COUNT(t) FROM GfaTicket t WHERE t.closedAt IS NOT NULL AND DATE(t.closedAt) = CURRENT_DATE")
    long countClosedToday();

    Optional<GfaTicket> findTopByGuichetIdAndStatutInOrderByCreatedAtDesc(Long guichetId, List<String> statuts);

    @Query("""
            SELECT COUNT(t)
            FROM GfaTicket t
            WHERE t.service.id = :serviceId
              AND UPPER(t.statut) = 'EN_ATTENTE'
              AND t.createdAt <= :createdAt
            """)
    long countWaitingRank(@Param("serviceId") Long serviceId, @Param("createdAt") java.time.LocalDateTime createdAt);

    @Query("""
            SELECT t
            FROM GfaTicket t
            WHERE UPPER(t.statut) = 'EN_COURS'
            ORDER BY t.calledAt DESC, t.id DESC
            """)
    List<GfaTicket> findCurrentCallsOrdered();
}
