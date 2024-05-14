import React, { useState, useEffect } from 'react';
import DaylightChart from './DaylightChart';

const App = () => {
  const [daylightData, setDaylightData] = useState(null);
  const [cityName, setCityName] = useState('');
  const [daylightData2, setDaylightData2] = useState(null);
  const [cityName2, setCityName2] = useState('');

  // Get city names from the DOM
  const reactRoot = document.getElementById('react-root');
  const cityNameAttr = reactRoot.getAttribute('data-city-name');
  const cityName2Attr = reactRoot.getAttribute('data-city-name2');

  useEffect(() => {
    // Fetch data for both cities
    const fetchData = async () => {
      try {
        const response = await fetch(
          `/api/daylight/${encodeURIComponent(
            cityNameAttr
          )}/${encodeURIComponent(cityName2Attr)}`
        );
        const data = await response.json();

        if (data.daylightChanges) {
          setDaylightData(data.daylightChanges);
          setCityName(data.cityName);
        }
        if (data.daylightChanges2) {
          setDaylightData2(data.daylightChanges2);
          setCityName2(data.cityName2);
        }
      } catch (error) {
        console.error('Failed to fetch data:', error);
      }
    };

    if (cityNameAttr && cityName2Attr) {
      fetchData();
    }
  }, [cityNameAttr, cityName2Attr]);

  // Render the chart only when data is available for both cities
  return (
    <div>
      {daylightData && daylightData2 && cityName && cityName2 ? (
        <DaylightChart
          daylightChanges={daylightData}
          cityName={cityName}
          daylightChanges2={daylightData2}
          cityName2={cityName2}
        />
      ) : (
        <p>Loading...</p>
      )}
    </div>
  );
};

export default App;
