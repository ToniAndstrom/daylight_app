import React, { useState, useEffect } from 'react';
import DaylightChart from './DaylightChart';

const App = () => {
  const [daylightData, setDaylightData] = useState(null);
  const [, setCityName] = useState(null);
  const cityName = document
    .getElementById('react-root')
    .getAttribute('data-city-name');

  useEffect(() => {
    // Replace with your actual data fetching logic
    const fetchData = async () => {
      try {
        // Simulated fetch request
        const response = await fetch(
          `/api/daylight/${encodeURIComponent(cityName)}`
        );
        const data = await response.json();
        setDaylightData(data.daylightChanges);
        setCityName(data.cityName);
      } catch (error) {
        console.error('Failed to fetch data:', error);
      }
    };

    fetchData();
  }, [cityName]);

  // Render the chart only when data is available
  return (
    <div>
      {daylightData && cityName ? (
        <DaylightChart daylightChanges={daylightData} cityName={cityName} />
      ) : (
        <p>Loading...</p>
      )}
    </div>
  );
};

export default App;
