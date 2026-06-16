using AutoMapper;
using Project.Gawad.Application;

namespace Project.Gawad.Tests.Application.Profiles;

public class AutoMapperProfileFixture
{
    public AutoMapperProfileFixture()
    {
        var config = new MapperConfiguration(cfg => { cfg.AddMaps(typeof(GawadApplicationEntry).Assembly); });

        // Create the mapper instance
        Mapper = config.CreateMapper();
    }

    public IMapper Mapper { get; }
}