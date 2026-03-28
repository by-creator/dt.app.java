package com.dtapp.repository;

import com.dtapp.entity.GfaWifiSettings;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;

import java.util.Optional;

@Repository
public interface GfaWifiSettingsRepository extends JpaRepository<GfaWifiSettings, Long> {

    Optional<GfaWifiSettings> findTopByOrderByIdAsc();
}
